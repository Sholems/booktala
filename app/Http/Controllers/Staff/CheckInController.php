<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\CheckInSearchRequest;
use App\Models\Reservation;
use App\Services\ReservationLifecycle;
use App\Support\PropertyAccess;
use Illuminate\Support\Facades\Auth;

class CheckInController extends Controller
{
    public function index()
    {
        $todayCheckIns = Reservation::visibleTo(Auth::user())
            ->whereDate('check_in', today())
            ->where('booking_status', 'confirmed')
            ->with('property', 'user', 'guestCheckin')
            ->get();

        return view('staff.checkin.index', compact('todayCheckIns'));
    }

    public function search(CheckInSearchRequest $request)
    {
        $request->validated();
        $search = $request->search;

        $reservations = Reservation::visibleTo(Auth::user())->where(function ($q) use ($request) {
            $q->where('booking_ref', 'like', "%{$request->search}%")
                ->orWhereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
        })
            ->whereIn('booking_status', ['confirmed', 'checked_in'])
            ->with('property', 'user', 'guestCheckin')
            ->take(10)
            ->get();

        return view('staff.checkin.search', compact('reservations', 'search'));
    }

    public function processCheckIn(Reservation $reservation, ReservationLifecycle $lifecycle)
    {
        PropertyAccess::authorize(Auth::user(), $reservation->property_id);

        $lifecycle->checkIn($reservation, Auth::user());

        return redirect()->back()->with('success', 'Guest checked in successfully');
    }

    public function checkoutIndex()
    {
        // Due today, plus anyone still checked in after their check-out date.
        $todayCheckOuts = Reservation::visibleTo(Auth::user())
            ->whereDate('check_out', '<=', today())
            ->where('booking_status', 'checked_in')
            ->with('property', 'user')
            ->get();

        return view('staff.checkout.index', compact('todayCheckOuts'));
    }

    public function processCheckOut(Reservation $reservation, ReservationLifecycle $lifecycle)
    {
        PropertyAccess::authorize(Auth::user(), $reservation->property_id);

        $lifecycle->checkOut($reservation, Auth::user());

        return redirect()->back()->with('success', 'Guest checked out successfully');
    }

    /**
     * The guest's online check-in details, for the front desk to verify at arrival.
     */
    public function details(Reservation $reservation)
    {
        PropertyAccess::authorize(Auth::user(), $reservation->property_id);

        $reservation->load('property', 'user', 'guestCheckin.verifiedBy');

        return view('staff.checkin.details', compact('reservation'));
    }

    public function verifyId(Reservation $reservation)
    {
        PropertyAccess::authorize(Auth::user(), $reservation->property_id);

        $online = $reservation->guestCheckin;

        if (! $online) {
            return back()->with('error', 'This guest has not completed online check-in.');
        }
        if ($online->isVerified()) {
            return back()->with('error', 'This ID has already been verified.');
        }

        $online->update([
            'status' => 'confirmed',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        return back()->with('success', 'ID verified');
    }
}
