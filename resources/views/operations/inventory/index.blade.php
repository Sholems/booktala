@extends($layout)
@section('page_title', 'Inventory')
@section('content')
@php
    $field = 'w-full border rounded-lg px-3 py-2 text-sm';
    $low = $items->filter->isLow();
    $staffTypes = collect(\App\Models\StockMovement::TYPES)->filter(fn ($t, $k) => $canManage || $t[1]);
@endphp
<div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h2 class="text-xl font-bold">{{ $site->name }} · Inventory</h2>
        <p class="text-sm text-gray-500">{{ $items->count() }} {{ Str::plural('item', $items->count()) }}@if($low->isNotEmpty()) · <span class="text-orange-600">{{ $low->count() }} low</span>@endif</p>
    </div>
    @include('operations._site-picker')
</div>

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <ul class="divide-y">
                @forelse($items as $item)
                <li class="px-5 py-3 {{ $item->isLow() ? 'bg-orange-50/60' : '' }}" x-data="{ open: false }">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 justify-between">
                        <div class="min-w-0">
                            <a href="{{ route($p . '.inventory.show', $item) }}" class="font-medium hover:text-primary">{{ $item->name }}</a>
                            <div class="text-xs text-gray-500">
                                {{ \App\Models\InventoryItem::CATEGORIES[$item->category] ?? $item->category }}
                                @if($item->reorder_level > 0) · reorder at {{ $item->quantityLabel($item->reorder_level) }}@endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <div class="font-semibold {{ $item->isLow() ? 'text-orange-700' : '' }}">{{ $item->quantityLabel($item->on_hand) }}</div>
                                @if($item->tracks_laundry && $item->at_laundry > 0)<div class="text-xs text-blue-600">+ {{ $item->quantityLabel($item->at_laundry) }} at laundry</div>@endif
                            </div>
                            <button type="button" @click="open = !open" class="px-3 py-1.5 rounded-lg bg-white ring-1 ring-gray-300 text-sm hover:ring-primary" :aria-expanded="open">Record</button>
                        </div>
                    </div>
                    <form x-show="open" x-cloak method="POST" action="{{ route($p . '.inventory.movements.store', $item) }}" class="mt-3 grid grid-cols-2 md:grid-cols-5 gap-2">
                        @csrf
                        <select name="type" required class="{{ $field }} col-span-2 md:col-span-1">
                            @foreach($staffTypes as $type => [$label])
                            @continue(in_array($type, ['to_laundry', 'from_laundry'], true) && !$item->tracks_laundry)
                            <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="quantity" required step="0.01" min="0" placeholder="Qty ({{ $item->unit }})" class="{{ $field }}">
                        @if($properties->count() > 1)
                        <select name="property_id" class="{{ $field }}">
                            <option value="">Flat (optional)</option>
                            @foreach($properties as $property)
                            <option value="{{ $property->id }}">{{ $property->title }}</option>
                            @endforeach
                        </select>
                        @endif
                        <input type="text" name="note" maxlength="255" placeholder="Note" class="{{ $field }}">
                        <button type="submit" class="px-3 py-2 rounded-lg bg-primary text-white text-sm hover:bg-primary/90">Save</button>
                    </form>
                </li>
                @empty
                <li class="px-5 py-6 text-sm text-gray-500">No stock items yet.@if($canManage) Add the first one under "Add an item".@endif</li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="px-5 py-3 border-b font-semibold">Recent activity</div>
            <ul class="divide-y text-sm">
                @forelse($recent as $movement)
                <li class="px-5 py-2 flex justify-between gap-3">
                    <span>{{ $movement->label() }}: {{ $movement->item->quantityLabel($movement->quantity) }} {{ $movement->item->name }}@if($movement->property) <span class="text-gray-500">· {{ $movement->property->title }}</span>@endif</span>
                    <span class="text-gray-500 whitespace-nowrap">{{ $movement->user->name ?? '' }} · {{ $movement->created_at->diffForHumans() }}</span>
                </li>
                @empty
                <li class="px-5 py-4 text-gray-500">Nothing recorded yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-5 text-sm text-gray-600">
            <h3 class="font-semibold text-gray-900 mb-2">How to record</h3>
            <ul class="list-disc pl-5 space-y-1">
                <li><strong>Used</strong>: toiletries, cleaning supplies, spares taken from the store.</li>
                <li><strong>Sent to / back from laundry</strong>: linen and towels.</li>
                <li><strong>Damaged / lost</strong>: written off.</li>
                @if($canManage)<li><strong>Received</strong> (deliveries) and <strong>stock count</strong> (sets the exact figure) are for supervisors.</li>@endif
            </ul>
        </div>

        @if($canManage)
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold mb-3">Add an item</h3>
            <form method="POST" action="{{ route($p . '.inventory.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="site_id" value="{{ $site->id }}">
                <input type="text" name="name" required maxlength="100" placeholder="e.g. Bath towel" value="{{ old('name') }}" class="{{ $field }}">
                <select name="category" required class="{{ $field }}">
                    @foreach(\App\Models\InventoryItem::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="grid grid-cols-3 gap-2">
                    <input type="text" name="unit" required maxlength="20" value="{{ old('unit', 'pcs') }}" aria-label="Unit" class="{{ $field }}">
                    <input type="number" name="opening_stock" step="0.01" min="0" placeholder="In stock" value="{{ old('opening_stock') }}" class="{{ $field }}">
                    <input type="number" name="reorder_level" step="0.01" min="0" placeholder="Reorder at" value="{{ old('reorder_level') }}" class="{{ $field }}">
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="tracks_laundry" value="1" @checked(old('tracks_laundry'))> Goes to the laundry (linen)</label>
                <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">Add item</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
