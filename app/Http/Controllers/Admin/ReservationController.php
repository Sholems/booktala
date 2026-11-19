<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelReservationRequest;
use App\Http\Requests\UpdateCautionRefundRequest;
use App\Http\Requests\UpdateReservationRefundRequest;
use App\Models\Reservation;
use App\Services\Finance\LedgerPostingService;
use App\Services\ReservationLifecycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::with(['user', 'property']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('booking_ref', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('property', function ($propertyQuery) use ($search) {
                        $propertyQuery->where('title', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('booking_status', $request->status);
        }

        if (array_key_exists((string) $request->query('queue'), Reservation::QUEUES)) {
            $query->inQueue($request->query('queue'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('check_in', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('check_out', '<=', $request->to_date);
        }

        $reservations = $query
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        return view('admin.reservations.index', compact('reservations'));
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['user', 'property', 'room', 'confirmedBy', 'cancelledBy']);

        return view('admin.reservations.show', compact('reservation'));
    }

    public function confirm(Reservation $reservation, ReservationLifecycle $lifecycle)
    {
        $lifecycle->confirm($reservation, auth()->user());

        return back()->with('success', 'Reservation confirmed');
    }

    public function checkIn(Request $request, Reservation $reservation, ReservationLifecycle $lifecycle)
    {
        $validated = $request->validate([
            'allow_unpaid' => ['nullable', 'boolean'],
        ]);

        $lifecycle->checkIn(
            $reservation,
            auth()->user(),
            $request->boolean('allow_unpaid'),
        );

        return back()->with('success', 'Guest checked in');
    }

    public function checkOut(Reservation $reservation, ReservationLifecycle $lifecycle)
    {
        $lifecycle->checkOut($reservation, auth()->user());

        return back()->with('success', 'Guest checked out');
    }

    public function noShow(Reservation $reservation, ReservationLifecycle $lifecycle)
    {
        $lifecycle->markNoShow($reservation, auth()->user());

        return back()->with('success', 'Reservation marked as a no-show');
    }

    public function cancel(CancelReservationRequest $request, Reservation $reservation, ReservationLifecycle $lifecycle)
    {
        $validated = $request->validated();

        $lifecycle->cancel($reservation, auth()->user(), $validated['reason'] ?? null);

        return back()->with('success', 'Reservation cancelled');
    }

    public function updateRefund(UpdateReservationRefundRequest $request, Reservation $reservation)
    {
        $validated = $request->validated();

        if ($reservation->refund_status !== 'pending') {
            return back()->with('error', 'There is no refund waiting for review on this booking.');
        }

        $reservation->update([
            'refund_status' => $validated['refund_status'],
        ]);

        return back()->with('success', $validated['refund_status'] === 'refunded' ? 'Refund marked as paid' : 'Refund declined');
    }

    public function updateCautionRefund(UpdateCautionRefundRequest $request, Reservation $reservation)
    {
        $validated = $request->validated();

        if ($reservation->caution_fee_status !== 'refund_pending') {
            return back()->with('error', 'This caution fee is not waiting for a refund decision.');
        }

        DB::transaction(function () use ($reservation, $validated) {
            $reservation->update([
                'caution_fee_status' => $validated['caution_fee_status'],
                'caution_refunded_at' => $validated['caution_fee_status'] === 'refunded' ? now() : null,
            ]);

            if ($validated['caution_fee_status'] === 'refunded') {
                app(LedgerPostingService::class)->postCautionRefund($reservation, null, auth()->id());
            } elseif ($validated['caution_fee_status'] === 'forfeited') {
                app(LedgerPostingService::class)->postCautionForfeiture($reservation, null, auth()->id());
            }
        });

        return back()->with(
            'success',
            $validated['caution_fee_status'] === 'refunded'
                ? 'Caution fee marked as refunded'
                : 'Caution fee marked as forfeited',
        );
    }
}
