<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\RoomServiceOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Guests request room service during a stay. Requests land in the staff
 * room service queue; staff set any charge and move them through to delivery.
 */
class RoomServiceController extends Controller
{
    public function index()
    {
        $orders = RoomServiceOrder::where('user_id', auth()->id())
            ->with('reservation.property')
            ->latest()
            ->paginate(20);

        return view('customer.room-service.index', compact('orders'));
    }

    public function create()
    {
        $reservations = $this->currentStays()->with('property')->get();

        if ($reservations->isEmpty()) {
            return redirect()->route('customer.room-service.index')
                ->with('error', 'Room service is available once you have checked in.');
        }

        $orderTypes = RoomServiceOrder::orderTypes();

        return view('customer.room-service.create', compact('reservations', 'orderTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reservation_id' => ['required', Rule::in($this->currentStays()->pluck('id')->all())],
            'order_type' => ['required', Rule::in(array_keys(RoomServiceOrder::orderTypes()))],
            'items' => 'required|array|min:1|max:20',
            'items.*.name' => 'required|string|max:100',
            'items.*.quantity' => 'required|integer|min:1|max:50',
            'special_instructions' => 'nullable|string|max:1000',
        ], [
            'reservation_id.in' => 'Room service is only available for a stay you are checked in to.',
        ]);

        // Guests describe what they need; prices are never taken from the request.
        $items = collect($validated['items'])->map(fn ($item) => [
            'name' => $item['name'],
            'quantity' => (int) $item['quantity'],
            'price' => 0,
        ])->values()->all();

        $order = RoomServiceOrder::create([
            'reservation_id' => $validated['reservation_id'],
            'user_id' => auth()->id(),
            'order_type' => $validated['order_type'],
            'items' => $items,
            'total_amount' => 0,
            'special_instructions' => $validated['special_instructions'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('customer.room-service.show', $order)
            ->with('success', 'Request sent. Our team has been notified.');
    }

    public function show(RoomServiceOrder $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $order->load('reservation.property');

        return view('customer.room-service.show', compact('order'));
    }

    public function cancel(RoomServiceOrder $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        // Once staff are on their way it is too late to cancel online.
        if (! in_array($order->status, ['pending', 'preparing'], true)) {
            return back()->with('error', 'This request can no longer be cancelled.');
        }

        $order->update(['status' => 'cancelled']);

        return back()->with('success', 'Request cancelled');
    }

    private function currentStays()
    {
        return Reservation::where('user_id', auth()->id())->where('booking_status', 'checked_in');
    }
}
