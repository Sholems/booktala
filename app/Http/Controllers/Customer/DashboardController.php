<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Wishlist;
use App\Support\GuestJourney;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $reservations = Reservation::where('user_id', Auth::id())
            ->with('property', 'guestCheckin', 'review')
            ->orderBy('check_in')
            ->get();

        // The stay happening now, otherwise the next confirmed or pending one.
        $currentStay = $reservations->firstWhere('booking_status', 'checked_in')
            ?? $reservations->first(fn ($r) => in_array($r->booking_status, ['confirmed', 'pending'], true) && $r->check_out->isAfter(today()));

        // Other bookings with something for the guest to do.
        $needsAction = $reservations
            ->reject(fn ($r) => $currentStay && $r->is($currentStay))
            ->filter(fn ($r) => GuestJourney::next($r) !== null
                && ($r->check_out->isAfter(today()->subDays(30))))
            ->take(3);

        $upcomingCount = $reservations->filter(fn ($r) => in_array($r->booking_status, ['pending', 'confirmed', 'checked_in'], true)
            && $r->check_out->isAfter(today()))->count();

        $wishlists = Wishlist::where('user_id', Auth::id())->get();
        $reviews = Review::where('user_id', Auth::id())->get();

        return view('customer.dashboard', compact('reservations', 'wishlists', 'reviews', 'currentStay', 'needsAction', 'upcomingCount'));
    }
}
