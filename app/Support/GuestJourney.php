<?php

namespace App\Support;

use App\Models\Reservation;
use App\Models\Setting;

/**
 * Where a guest is in their stay and what they should do next.
 * Used on the customer dashboard and booking page.
 */
class GuestJourney
{
    /**
     * Timeline steps with a state of done, current or upcoming.
     *
     * @return array<int, array{label: string, detail: ?string, state: string}>
     */
    public static function steps(Reservation $r): array
    {
        if (in_array($r->booking_status, ['cancelled', 'no_show'], true)) {
            return [
                ['label' => 'Booked', 'detail' => $r->created_at?->format('j M Y'), 'state' => 'done'],
                [
                    'label' => $r->booking_status === 'cancelled' ? 'Cancelled' : 'Marked as no-show',
                    'detail' => $r->cancelled_at?->format('j M Y'),
                    'state' => 'current',
                ],
            ];
        }

        $order = ['pending' => 0, 'confirmed' => 1, 'checked_in' => 3, 'checked_out' => 4];
        $position = $order[$r->booking_status] ?? 0;
        $onlineDone = $r->guestCheckin !== null || $position >= 3;

        $state = fn (bool $done, bool $current) => $done ? 'done' : ($current ? 'current' : 'upcoming');

        return [
            ['label' => 'Booked', 'detail' => $r->created_at?->format('j M Y'), 'state' => 'done'],
            ['label' => 'Confirmed', 'detail' => $r->confirmed_at?->format('j M Y'), 'state' => $state($position >= 1, $position === 0)],
            ['label' => 'Online check-in', 'detail' => $r->guestCheckin ? 'Details sent' : 'Optional, saves time', 'state' => $state($onlineDone, $position === 1)],
            ['label' => 'Checked in', 'detail' => $r->checked_in_at?->format('j M, g:i A') ?? $r->check_in->format('j M Y'), 'state' => $state($position >= 3, false)],
            ['label' => 'Checked out', 'detail' => $r->checked_out_at?->format('j M, g:i A') ?? $r->check_out->format('j M Y'), 'state' => $state($position >= 4, $position === 3)],
        ];
    }

    /**
     * The single most useful next action for the guest, or null when there is nothing to do.
     *
     * @return array{title: string, text: string, action: ?string, url: ?string, tone: string}|null
     */
    public static function next(Reservation $r): ?array
    {
        $phone = Setting::get('phone', config('brand.phone'));
        $checkInTime = Setting::get('check_in_time', '14:00');

        return match (true) {
            $r->booking_status === 'pending' && $r->payment_status === 'pending' => [
                'title' => 'Awaiting payment',
                'text' => "We haven't received payment for this booking yet. If you didn't finish paying, call us on {$phone} and we'll help.",
                'action' => null, 'url' => null, 'tone' => 'amber',
            ],
            $r->booking_status === 'pending' => [
                'title' => 'Being confirmed',
                'text' => 'Payment received. Our team is confirming your booking and will email you shortly.',
                'action' => null, 'url' => null, 'tone' => 'blue',
            ],
            $r->booking_status === 'confirmed' && $r->payment_status === 'partial' => [
                'title' => 'Balance due',
                'text' => 'Your 50% deposit is recorded. Pay the remaining balance before check-in.',
                'action' => 'Pay balance',
                'url' => route('customer.reservations.show', $r).'#payment',
                'tone' => 'amber',
            ],
            $r->booking_status === 'confirmed' && ! $r->guestCheckin && $r->check_out->isAfter(today()) => [
                'title' => 'Check in online',
                'text' => 'Send your ID and arrival details now so check-in at the front desk is quick.',
                'action' => 'Check in online', 'url' => route('customer.checkin.create', $r), 'tone' => 'gold',
            ],
            $r->booking_status === 'confirmed' => [
                'title' => "You're all set",
                'text' => 'Check-in from '.date('g:i A', strtotime($checkInTime)).' on '.$r->check_in->format('l, j F').'. Bring the ID you entered online.',
                'action' => null, 'url' => null, 'tone' => 'green',
            ],
            $r->booking_status === 'checked_in' => [
                'title' => 'Enjoy your stay',
                'text' => 'Check-out is on '.$r->check_out->format('l, j F').'. Need towels, food or anything else?',
                'action' => 'Request room service', 'url' => route('customer.room-service.create'), 'tone' => 'blue',
            ],
            $r->booking_status === 'checked_out' && ! $r->review => [
                'title' => 'How was your stay?',
                'text' => 'A quick review helps other guests and helps us improve.',
                'action' => 'Leave a review', 'url' => route('customer.reservations.show', $r).'#review', 'tone' => 'gold',
            ],
            $r->booking_status === 'cancelled' && $r->refund_status === 'pending' => [
                'title' => 'Refund under review',
                'text' => "We're reviewing your refund under the cancellation policy and will be in touch. Questions? Call {$phone}.",
                'action' => null, 'url' => null, 'tone' => 'amber',
            ],
            default => null,
        };
    }
}
