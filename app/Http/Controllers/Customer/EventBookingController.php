<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\EventBooking;
use App\Models\EventSpace;
use Illuminate\Http\Request;

class EventBookingController extends Controller
{
    public function index()
    {
        $bookings = EventBooking::where('user_id', auth()->id())
            ->with('eventSpace')
            ->latest()
            ->paginate(20);

        return view('customer.event-bookings.index', compact('bookings'));
    }

    public function create()
    {
        $spaces = EventSpace::where('is_active', true)->get();
        $eventTypes = EventBooking::eventTypes();

        return view('customer.event-bookings.create', compact('spaces', 'eventTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'event_space_id' => 'required|exists:event_spaces,id',
            'event_name' => 'required|string|max:255',
            'event_type' => 'required|in:wedding,birthday,corporate,conference,seminar,reunion,other',
            'event_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'num_guests' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $space = EventSpace::findOrFail($request->event_space_id);

        $hours = (strtotime($request->end_time) - strtotime($request->start_time)) / 3600;
        $totalPrice = $hours * $space->hourly_rate;

        $booking = EventBooking::create([
            'event_space_id' => $request->event_space_id,
            'user_id' => auth()->id(),
            'event_name' => $request->event_name,
            'event_type' => $request->event_type,
            'event_date' => $request->event_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'num_guests' => $request->num_guests,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        return redirect()->route('customer.event-bookings.show', $booking)
            ->with('success', 'Event booking submitted! We will review and confirm shortly.');
    }

    public function show(EventBooking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $booking->load('eventSpace');

        return view('customer.event-bookings.show', compact('booking'));
    }

    public function cancel(EventBooking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Cannot cancel this booking');
        }

        $booking->update(['status' => 'cancelled']);

        return back()->with('success', 'Booking cancelled');
    }
}
