<?php

namespace App\Services;

use App\Events\ReservationStatusChanged;
use App\Exceptions\InvalidReservationTransition;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Unit;
use App\Models\User;
use App\Services\Finance\LedgerPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The single place where a reservation's booking status changes.
 *
 * Admin, staff and customer actions all go through here so every portal
 * follows the same rules, records who acted and when, and fires the same
 * ReservationStatusChanged event.
 *
 *   pending   -> confirmed | cancelled
 *   confirmed -> checked_in | cancelled | no_show
 *   checked_in -> checked_out
 */
class ReservationLifecycle
{
    public const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['checked_in', 'cancelled', 'no_show'],
        'checked_in' => ['checked_out'],
        'checked_out' => [],
        'cancelled' => [],
        'no_show' => [],
    ];

    private const LABELS = [
        'pending' => 'pending',
        'confirmed' => 'confirmed',
        'checked_in' => 'checked in',
        'checked_out' => 'checked out',
        'cancelled' => 'cancelled',
        'no_show' => 'a no-show',
    ];

    public function canTransition(Reservation $reservation, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$reservation->booking_status] ?? [], true);
    }

    public function confirm(Reservation $reservation, User $actor): Reservation
    {
        return $this->transition($reservation, 'confirmed', $actor, fn () => [
            'confirmed_by' => $actor->id,
            'confirmed_at' => now(),
        ]);
    }

    public function confirmAfterPayment(Reservation $reservation): Reservation
    {
        $confirmed = $this->transition($reservation, 'confirmed', null, fn (Reservation $locked) => [
            'confirmed_by' => null,
            'confirmed_at' => now(),
            'hold_expires_at' => null,
            'caution_fee_status' => $locked->payment_status === 'paid' && (float) $locked->caution_fee > 0
                ? 'held'
                : $locked->caution_fee_status,
        ]);

        try {
            app(AffiliateCommissionService::class)->recordReferral($confirmed);
        } catch (\Throwable $e) {
            Log::warning('Failed to record affiliate referral: '.$e->getMessage());
        }

        return $confirmed;
    }

    /**
     * @param  bool  $allowUnpaid  Checking in a guest who has not paid needs the reservation.checkin-unpaid
     *                             permission (admins) and must be asked for explicitly.
     */
    public function checkIn(Reservation $reservation, User $actor, bool $allowUnpaid = false, ?int $roomId = null): Reservation
    {
        return $this->transition($reservation, 'checked_in', $actor, function (Reservation $locked) use ($actor, $allowUnpaid, $roomId) {
            $unit = Unit::whereKey($locked->property_id)->lockForUpdate()->firstOrFail();
            if ($unit->status !== 'active') {
                throw new InvalidReservationTransition('This unit is not available for check-in.');
            }
            if ($locked->check_in->isAfter(today())) {
                throw new InvalidReservationTransition('This guest cannot check in before '.$locked->check_in->format('j M Y').'.');
            }
            if (! $locked->check_out->isAfter(today())) {
                throw new InvalidReservationTransition('This stay ended on '.$locked->check_out->format('j M Y').'. Mark it as a no-show instead.');
            }
            if ($locked->payment_status !== 'paid'
                && ! ($allowUnpaid && $actor->can('reservation.checkin-unpaid'))) {
                throw new InvalidReservationTransition('The booking balance must be paid before check-in. An admin must approve checking in a guest with an outstanding balance.');
            }

            // The online check-in (if any) is now fulfilled at the desk.
            $locked->guestCheckin()->update(['status' => 'completed']);

            $changes = ['checked_in_at' => now(), 'checked_in_by' => $actor->id];

            $legacyRoomId = $roomId ?? $locked->room_id;
            if ($legacyRoomId !== null) {
                $room = Room::whereKey($legacyRoomId)->lockForUpdate()->firstOrFail();

                if ((int) $room->unit_id !== (int) $locked->property_id) {
                    throw new InvalidReservationTransition('The legacy room mapping does not match the reserved unit.');
                }

                if (! in_array($room->status, ['available', 'reserved'], true)) {
                    throw new InvalidReservationTransition('This room is not available for check-in.');
                }

                $conflict = Reservation::where('room_id', $room->id)
                    ->where('id', '!=', $locked->id)
                    ->whereIn('booking_status', ['confirmed', 'checked_in'])
                    ->whereDate('check_in', '<', $locked->check_out)
                    ->whereDate('check_out', '>', $locked->check_in)
                    ->exists();

                if ($conflict) {
                    throw new InvalidReservationTransition('This room is already assigned to an active stay.');
                }

                $room->update(['status' => 'occupied']);
                $changes['room_id'] = $room->id;
            }

            return $changes;
        });
    }

    public function checkOut(Reservation $reservation, User $actor): Reservation
    {
        return $this->transition($reservation, 'checked_out', $actor, function (Reservation $locked) use ($actor) {
            $changes = [
                'checked_out_at' => now(),
                'checked_out_by' => $actor->id,
                'caution_fee_status' => $locked->caution_fee_status === 'held' ? 'refund_pending' : $locked->caution_fee_status,
            ];

            if ($locked->room_id) {
                $room = Room::whereKey($locked->room_id)->lockForUpdate()->first();
                $room?->update(['status' => 'cleaning']);
            }

            if ($locked->payment_status === 'paid') {
                app(LedgerPostingService::class)->postStayCompletion($locked, $actor->id);
            }

            return $changes;
        });
    }

    public function markNoShow(Reservation $reservation, User $actor): Reservation
    {
        return $this->transition($reservation, 'no_show', $actor, function (Reservation $locked) {
            if ($locked->check_in->isAfter(today())) {
                throw new InvalidReservationTransition('A booking can only be marked as a no-show on or after its check-in date.');
            }

            return [
                'caution_fee_status' => $locked->caution_fee_status === 'held' ? 'refund_pending' : $locked->caution_fee_status,
            ];
        });
    }

    public function cancel(Reservation $reservation, User $actor, ?string $reason = null): Reservation
    {
        $cancelled = $this->transition($reservation, 'cancelled', $actor, function (Reservation $locked) use ($actor, $reason) {
            $isGuest = $locked->user_id === $actor->id && ! $this->isAdmin($actor);
            if ($isGuest && ! $locked->check_in->isAfter(today())) {
                throw new InvalidReservationTransition('Bookings can only be cancelled online before the check-in date. Please contact us to make changes.');
            }

            return [
                'cancellation_reason' => $reason,
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'hold_expires_at' => null,
                // Money already taken has to be reviewed against the cancellation policy.
                'refund_status' => in_array($locked->payment_status, ['paid', 'partial'], true) ? 'pending' : null,
                'caution_fee_status' => $locked->caution_fee_status === 'held' ? 'refund_pending' : $locked->caution_fee_status,
            ];
        });

        try {
            app(AffiliateCommissionService::class)->voidReferral($cancelled, $reason ?: 'Reservation cancelled');
        } catch (\Throwable $e) {
            Log::warning('Failed to void affiliate referral: '.$e->getMessage());
        }

        return $cancelled;
    }

    /**
     * @param  callable(Reservation): array  $changes  Checks the locked row and returns the extra columns to save.
     */
    private function transition(Reservation $reservation, string $to, ?User $actor, callable $changes): Reservation
    {
        $from = DB::transaction(function () use ($reservation, $to, $changes) {
            // Re-read under a lock so two people acting at once cannot both succeed.
            $locked = Reservation::whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();
            $from = $locked->booking_status;

            if (! $this->canTransition($locked, $to)) {
                throw new InvalidReservationTransition(sprintf(
                    'This booking is %s, so it cannot be marked as %s.',
                    self::LABELS[$from] ?? $from,
                    self::LABELS[$to] ?? $to,
                ));
            }

            $locked->update(['booking_status' => $to] + $changes($locked));
            $reservation->setRawAttributes($locked->getAttributes(), true);

            return $from;
        });

        event(new ReservationStatusChanged($reservation, $from, $to, $actor?->id));

        return $reservation;
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
