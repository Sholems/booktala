{{-- Room service queue, shared by staff and admin. Needs $orders, $filter, $openCount, $routePrefix. --}}
@php use App\Models\RoomServiceOrder; @endphp
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h2 class="text-2xl font-bold">Room Service</h2>
        <p class="text-sm text-gray-500">Guest requests during their stay. Oldest open requests are shown first.</p>
    </div>
    <div class="inline-flex rounded-lg border bg-white p-1 text-sm">
        <a href="{{ route($routePrefix . '.index', ['show' => 'open']) }}" class="px-4 py-1.5 rounded {{ $filter === 'open' ? 'bg-primary text-white' : 'text-gray-600 hover:text-primary' }}">Open ({{ $openCount }})</a>
        <a href="{{ route($routePrefix . '.index', ['show' => 'done']) }}" class="px-4 py-1.5 rounded {{ $filter === 'done' ? 'bg-primary text-white' : 'text-gray-600 hover:text-primary' }}">Completed</a>
    </div>
</div>

<div class="space-y-4">
    @forelse($orders as $order)
    @php
        $statusClass = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'preparing' => 'bg-blue-100 text-blue-800',
            'on_the_way' => 'bg-purple-100 text-purple-800',
            'delivered' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-gray-100 text-gray-700',
        ][$order->status] ?? 'bg-gray-100 text-gray-700';
    @endphp
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-1 text-xs rounded-full {{ $statusClass }}">{{ RoomServiceOrder::statuses()[$order->status] ?? $order->status }}</span>
                    <span class="text-sm font-semibold">{{ RoomServiceOrder::orderTypes()[$order->order_type] ?? ucfirst($order->order_type) }}</span>
                    <span class="text-xs text-gray-500">#{{ $order->id }} · {{ $order->created_at->diffForHumans() }}</span>
                </div>
                <p class="mt-2 text-sm">
                    <strong>{{ $order->user->name ?? 'Guest' }}</strong>
                    · {{ $order->reservation->property->name ?? 'Apartment' }}
                    · <span class="text-gray-500">{{ $order->reservation->booking_ref ?? '' }}</span>
                </p>
                <ul class="mt-2 text-sm text-gray-700 list-disc pl-5">
                    @foreach($order->items ?? [] as $item)
                    <li>{{ $item['quantity'] ?? 1 }} × {{ $item['name'] ?? 'Item' }}</li>
                    @endforeach
                </ul>
                @if($order->special_instructions)
                <p class="mt-2 text-sm text-gray-600 whitespace-pre-line"><span class="text-gray-500">Notes:</span> {{ $order->special_instructions }}</p>
                @endif
                <p class="mt-2 text-xs text-gray-500">
                    @if((float) $order->total_amount > 0) Charge: ₦{{ number_format($order->total_amount, 2) }} · @endif
                    @if($order->handledBy) Handled by {{ $order->handledBy->name }} @endif
                    @if($order->delivered_at) · Delivered {{ $order->delivered_at->format('M d, g:i A') }} @endif
                </p>
            </div>

            @if($label = RoomServiceOrder::nextActionLabel($order->status))
            <div class="lg:w-72 space-y-2">
                <form method="POST" action="{{ route($routePrefix . '.advance', $order) }}" class="space-y-2">
                    @csrf
                    @method('PATCH')
                    <label class="block text-xs text-gray-500">Charge to guest (₦, optional)</label>
                    <input type="number" name="charge" min="0" step="0.01" value="{{ (float) $order->total_amount > 0 ? $order->total_amount : '' }}" placeholder="0 = free" class="w-full border rounded px-3 py-1.5 text-sm">
                    <button class="w-full px-3 py-2 rounded bg-primary text-white text-sm hover:bg-primary/90">{{ $label }}</button>
                </form>
                <form method="POST" action="{{ route($routePrefix . '.decline', $order) }}" class="flex gap-2">
                    @csrf
                    @method('PATCH')
                    <input type="text" name="reason" maxlength="255" placeholder="Reason (optional)" class="flex-1 border rounded px-3 py-1.5 text-sm">
                    <button class="px-3 py-1.5 rounded border border-red-500 text-red-600 text-sm hover:bg-red-50" onclick="return confirm('Decline this request?')">Decline</button>
                </form>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
        {{ $filter === 'open' ? 'No open room service requests.' : 'No completed requests yet.' }}
    </div>
    @endforelse
</div>

<div class="mt-4">{{ $orders->links() }}</div>
