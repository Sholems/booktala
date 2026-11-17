<?php

namespace App\Http\Controllers\Concerns;

use App\Models\RoomServiceOrder;
use App\Support\PropertyAccess;
use Illuminate\Http\Request;

/**
 * The room service queue shared by the staff and admin portals.
 * Each controller supplies its view and route prefix.
 */
trait ManagesRoomServiceQueue
{
    abstract protected function queueView(): string;

    abstract protected function routePrefix(): string;

    public function index(Request $request)
    {
        $filter = $request->query('show', 'open');

        $orders = RoomServiceOrder::visibleTo($request->user())
            ->with(['reservation.property', 'user', 'handledBy'])
            ->when($filter === 'open', fn ($q) => $q->whereIn('status', RoomServiceOrder::OPEN_STATUSES)->oldest())
            ->when($filter === 'done', fn ($q) => $q->whereIn('status', ['delivered', 'cancelled'])->latest('updated_at'))
            ->paginate(25)
            ->withQueryString();

        $openCount = RoomServiceOrder::visibleTo($request->user())->whereIn('status', RoomServiceOrder::OPEN_STATUSES)->count();

        return view($this->queueView(), [
            'orders' => $orders,
            'filter' => $filter,
            'openCount' => $openCount,
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function advance(Request $request, RoomServiceOrder $order)
    {
        PropertyAccess::authorize($request->user(), $order->reservation?->property_id);

        $validated = $request->validate([
            'charge' => 'nullable|numeric|min:0|max:10000000',
        ]);

        if (!$order->advance($request->user(), isset($validated['charge']) ? (float) $validated['charge'] : null)) {
            return back()->with('error', 'This request is already ' . str_replace('_', ' ', $order->status) . '.');
        }

        return back()->with('success', 'Request ' . RoomServiceOrder::statuses()[$order->status] . '.');
    }

    public function decline(Request $request, RoomServiceOrder $order)
    {
        PropertyAccess::authorize($request->user(), $order->reservation?->property_id);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        if (!in_array($order->status, RoomServiceOrder::OPEN_STATUSES, true)) {
            return back()->with('error', 'Only open requests can be declined.');
        }

        $note = trim('Declined by ' . $request->user()->name . ($validated['reason'] ?? null ? ': ' . $validated['reason'] : ''));
        $order->update([
            'status' => 'cancelled',
            'handled_by' => $request->user()->id,
            'special_instructions' => trim(($order->special_instructions ? $order->special_instructions . "\n" : '') . '[' . $note . ']'),
        ]);

        return back()->with('success', 'Request declined');
    }
}
