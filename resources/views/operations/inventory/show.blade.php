@extends($layout)
@section('page_title', 'Inventory')
@section('content')
<div class="max-w-4xl">
    <a href="{{ route($p . '.inventory.index', ['site' => $item->site_id]) }}" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-arrow-left mr-1"></i> {{ $item->site->name }} inventory</a>
    <div class="mt-1 mb-4 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">{{ $item->name }}</h2>
            <p class="text-sm text-gray-500">
                {{ $item->quantityLabel($item->on_hand) }} on hand
                @if($item->tracks_laundry) · {{ $item->quantityLabel($item->at_laundry) }} at laundry @endif
                @if($item->isLow()) · <span class="text-orange-600">below reorder level ({{ $item->quantityLabel($item->reorder_level) }})</span>@endif
            </p>
        </div>
    </div>

    @if($canManage)
    <form method="POST" action="{{ route($p . '.inventory.update', $item) }}" class="mb-6 bg-white rounded-lg shadow p-4 flex flex-wrap items-end gap-3 text-sm">
        @csrf @method('PATCH')
        <div>
            <label class="block text-xs text-gray-500 mb-1">Reorder at</label>
            <input type="number" name="reorder_level" step="0.01" min="0" value="{{ $item->reorder_level }}" class="w-28 border rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Unit</label>
            <input type="text" name="unit" maxlength="20" value="{{ $item->unit }}" class="w-24 border rounded-lg px-3 py-2">
        </div>
        <label class="flex items-center gap-2 pb-2"><input type="hidden" name="tracks_laundry" value="0"><input type="checkbox" name="tracks_laundry" value="1" @checked($item->tracks_laundry)> Laundry</label>
        <label class="flex items-center gap-2 pb-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($item->is_active)> In use</label>
        <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90">Save</button>
    </form>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
        <table class="min-w-full mobile-card-table text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">When</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Movement</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">On hand after</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">By</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($movements as $movement)
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap" data-label="When">{{ $movement->created_at->format('D j M, g:i A') }}</td>
                    <td class="px-4 py-3" data-label="Movement">
                        {{ $movement->label() }}@if($movement->property) · {{ $movement->property->title }}@endif
                        @if($movement->unit_cost) <span class="text-gray-500">· ₦{{ number_format($movement->unit_cost, 2) }} each</span>@endif
                        @if($movement->note)<div class="text-xs text-gray-500">{{ $movement->note }}</div>@endif
                    </td>
                    <td class="px-4 py-3 text-right" data-label="Qty">{{ $item->quantityLabel($movement->quantity) }}</td>
                    <td class="px-4 py-3 text-right" data-label="On hand after">{{ $item->quantityLabel($movement->on_hand_after) }}</td>
                    <td class="px-4 py-3" data-label="By">{{ $movement->user->name ?? '' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No movements yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $movements->links() }}</div>
</div>
@endsection
