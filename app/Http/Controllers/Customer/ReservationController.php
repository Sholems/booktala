<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationLifecycle;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index()
    {
        $reservations = Reservation::where('user_id', auth()->id())
            ->with('property', 'payments')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('customer.reservations.index', compact('reservations'));
    }

    public function show(Reservation $reservation)
    {
        if ($reservation->user_id !== auth()->id()) {
            abort(403);
        }
        
        $reservation->load('property', 'payments', 'review', 'guestCheckin');
        return view('customer.reservations.show', compact('reservation'));
    }

    public function cancel(Request $request, Reservation $reservation, ReservationLifecycle $lifecycle)
    {
        if ($reservation->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reservation = $lifecycle->cancel($reservation, auth()->user(), $validated['reason'] ?? null);

        $message = $reservation->refund_status === 'pending'
            ? 'Booking cancelled. Your refund will be reviewed under the cancellation policy and we will contact you.'
            : 'Booking cancelled.';

        return redirect()->route('customer.reservations.show', $reservation)->with('success', $message);
    }

    public function submitReview(Request $request, Reservation $reservation)
    {
        if ($reservation->user_id !== auth()->id()) {
            abort(403);
        }
        
        if ($reservation->booking_status !== 'checked_out') {
            return back()->with('error', 'You can only review after checkout');
        }
        
        if ($reservation->review()->exists()) {
            return back()->with('error', 'You have already reviewed this stay.');
        }

        $validated = $request->validate([
            'overall_rating' => 'required|integer|min:1|max:5',
            'cleanliness_rating' => 'nullable|integer|min:1|max:5',
            'location_rating' => 'nullable|integer|min:1|max:5',
            'value_rating' => 'nullable|integer|min:1|max:5',
            'communication_rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);

        // Detailed ratings are optional; fall back to the overall score.
        $overall = (int) $validated['overall_rating'];
        $reservation->review()->create([
            'user_id' => auth()->id(),
            'property_id' => $reservation->property_id,
            'overall_rating' => $overall,
            'cleanliness_rating' => $validated['cleanliness_rating'] ?? $overall,
            'location_rating' => $validated['location_rating'] ?? $overall,
            'value_rating' => $validated['value_rating'] ?? $overall,
            'communication_rating' => $validated['communication_rating'] ?? $overall,
            'comment' => $validated['comment'] ?? null,
            'is_published' => true
        ]);
        
        return back()->with('success', 'Review submitted successfully');
    }
}
