<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventBooking;
use App\Models\EventSpace;
use Illuminate\Http\Request;

class EventBookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = EventBooking::with(['user', 'eventSpace'])
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->search, fn($q, $search) => $q->where('event_name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.event-bookings.index', compact('bookings'));
    }

    public function show(EventBooking $booking)
    {
        $booking->load(['user', 'eventSpace']);
        return view('admin.event-bookings.show', compact('booking'));
    }

    public function update(Request $request, EventBooking $booking)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $booking->update(['status' => $request->status]);

        return back()->with('success', 'Booking updated successfully');
    }

    public function spaces()
    {
        $spaces = EventSpace::all();
        return view('admin.event-spaces.index', compact('spaces'));
    }

    public function createSpace()
    {
        return view('admin.event-spaces.create');
    }

    public function storeSpace(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'size' => 'nullable|integer',
            'hourly_rate' => 'nullable|numeric|min:0',
            'daily_rate' => 'nullable|numeric|min:0',
            'amenities' => 'nullable|array',
        ]);

        EventSpace::create($request->all() + ['is_active' => true]);

        return redirect()->route('admin.event-spaces.index')->with('success', 'Event space created');
    }

    public function editSpace(EventSpace $space)
    {
        return view('admin.event-spaces.edit', compact('space'));
    }

    public function updateSpace(Request $request, EventSpace $space)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'hourly_rate' => 'nullable|numeric|min:0',
            'daily_rate' => 'nullable|numeric|min:0',
        ]);

        $space->update($request->all());

        return back()->with('success', 'Event space updated');
    }

    public function destroySpace(EventSpace $space)
    {
        $space->delete();
        return back()->with('success', 'Event space deleted');
    }
}