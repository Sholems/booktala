<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CheckinStoreRequest;
use App\Models\GuestCheckin;
use App\Models\Reservation;

/**
 * Online pre-arrival check-in. The details a guest sends here appear on the
 * staff arrivals list and the admin booking page so the front desk can verify them.
 */
class CheckinController extends Controller
{
    public function index()
    {
        // Confirmed stays that have not started yet and have no online check-in.
        $upcomingCheckins = Reservation::where('user_id', auth()->id())
            ->where('booking_status', 'confirmed')
            ->whereDate('check_out', '>', today())
            ->whereDoesntHave('guestCheckin')
            ->with('property')
            ->orderBy('check_in')
            ->get();

        return view('customer.checkin.index', compact('upcomingCheckins'));
    }

    public function create(Reservation $reservation)
    {
        $this->ensureCanCheckIn($reservation);

        $checkin = $reservation->guestCheckin;
        $user = $reservation->user;

        return view('customer.checkin.create', compact('reservation', 'checkin', 'user'));
    }

    public function store(CheckinStoreRequest $request, Reservation $reservation)
    {
        $this->ensureCanCheckIn($reservation);

        if ($reservation->guestCheckin?->isVerified()) {
            return redirect()->route('customer.checkin.show', $reservation->guestCheckin)
                ->with('error', 'Your details have already been verified by the front desk. Please contact us to change them.');
        }

        $validated = $request->validated();

        $checkin = GuestCheckin::updateOrCreate(
            ['reservation_id' => $reservation->id],
            $validated + [
                'check_in_date' => $reservation->check_in,
                'num_children' => $validated['num_children'] ?? 0,
                'status' => 'pending',
            ]
        );

        return redirect()->route('customer.checkin.show', $checkin)
            ->with('success', 'Online check-in sent. Please bring the ID you entered; the front desk will check it when you arrive.');
    }

    public function show(GuestCheckin $checkin)
    {
        $checkin->load('reservation.property');
        abort_unless($checkin->reservation && $checkin->reservation->user_id === auth()->id(), 403);

        return view('customer.checkin.show', compact('checkin'));
    }

    /**
     * Only the guest's own confirmed booking, before the stay ends, can be checked in online.
     */
    private function ensureCanCheckIn(Reservation $reservation): void
    {
        abort_unless($reservation->user_id === auth()->id(), 403);

        if ($reservation->booking_status !== 'confirmed' || ! $reservation->check_out->isAfter(today())) {
            abort(redirect()->route('customer.checkin.index')
                ->with('error', 'Online check-in is only available for confirmed upcoming stays.'));
        }
    }
}
