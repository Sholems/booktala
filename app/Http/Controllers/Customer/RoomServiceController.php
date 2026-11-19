<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\RoomServiceOrder;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Guests request room service and concierge during a stay (food, laundry, extra housekeeping, airport transfer).
 * Requests land in the staff queue; payment is charged to folio or settled via Paystack.
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
                ->with('error', 'Concierge and room service is available once you have checked in.');
        }

        $orderTypes = RoomServiceOrder::orderTypes();
        $presetCatalog = RoomServiceOrder::PRESET_CATALOG;

        return view('customer.room-service.create', compact('reservations', 'orderTypes', 'presetCatalog'));
    }

    public function store(Request $request, WhatsAppNotificationService $whatsAppService)
    {
        $validated = $request->validate([
            'reservation_id' => ['required', Rule::in($this->currentStays()->pluck('id')->all())],
            'order_type' => ['required', Rule::in(array_keys(RoomServiceOrder::orderTypes()))],
            'items' => 'required|array|min:1|max:20',
            'items.*.name' => 'required|string|max:100',
            'items.*.quantity' => 'required|integer|min:1|max:50',
            'items.*.price' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:folio,paystack,cash',
            'special_instructions' => 'nullable|string|max:1000',
            'pickup_location' => 'nullable|string|max:255',
            'pickup_time' => 'nullable|date',
            'flight_number' => 'nullable|string|max:50',
        ], [
            'reservation_id.in' => 'Concierge service is only available for a stay you are checked in to.',
        ]);

        $catalog = RoomServiceOrder::PRESET_CATALOG[$validated['order_type']] ?? [];
        $catalogMap = collect($catalog)->keyBy('name');

        $totalAmount = 0.0;
        $items = collect($validated['items'])->map(function ($item) use ($catalogMap, &$totalAmount) {
            $name = $item['name'];
            $qty = (int) $item['quantity'];

            // Match preset catalog price if available, otherwise take submitted or 0
            $price = 0.0;
            if ($catalogItem = $catalogMap->get($name)) {
                $price = (float) $catalogItem['price'];
            } elseif (! empty($item['price'])) {
                $price = (float) $item['price'];
            }

            $totalAmount += ($price * $qty);

            return [
                'name' => $name,
                'quantity' => $qty,
                'price' => $price,
            ];
        })->values()->all();

        $paymentStatus = match ($validated['payment_method']) {
            'folio' => RoomServiceOrder::PAYMENT_STATUS_CHARGED_TO_FOLIO,
            'paystack' => RoomServiceOrder::PAYMENT_STATUS_UNPAID,
            default => RoomServiceOrder::PAYMENT_STATUS_UNPAID,
        };

        $order = RoomServiceOrder::create([
            'reservation_id' => $validated['reservation_id'],
            'user_id' => auth()->id(),
            'order_type' => $validated['order_type'],
            'items' => $items,
            'total_amount' => $totalAmount,
            'payment_method' => $validated['payment_method'],
            'payment_status' => $paymentStatus,
            'paystack_reference' => $validated['payment_method'] === 'paystack' ? 'PST-CON-'.uniqid() : null,
            'special_instructions' => $validated['special_instructions'] ?? null,
            'pickup_location' => $validated['pickup_location'] ?? null,
            'pickup_time' => $validated['pickup_time'] ?? null,
            'flight_number' => $validated['flight_number'] ?? null,
            'status' => 'pending',
        ]);

        // Send WhatsApp alert
        try {
            $whatsAppService->sendConciergeUpdate($order);
        } catch (\Throwable) {
            // Non-blocking
        }

        return redirect()->route('customer.room-service.show', $order)
            ->with('success', 'Concierge request sent. Our team has been notified.');
    }

    public function show(RoomServiceOrder $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $order->load(['reservation.property', 'handledBy']);

        return view('customer.room-service.show', compact('order'));
    }

    public function cancel(RoomServiceOrder $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

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
