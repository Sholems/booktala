@extends('layouts.admin')

@section('page_title', 'Properties')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <h2 class="text-2xl font-bold">All Properties</h2>
    <a href="{{ route('admin.properties.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 w-full sm:w-auto text-center">
        <i class="fas fa-plus mr-2"></i> Add Property
    </a>
</div>

{{-- Search & Filters --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('admin.properties.index') }}" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search properties..." 
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="md:w-40">
            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="md:w-40">
            <select name="featured" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All</option>
                <option value="1" {{ request('featured') == '1' ? 'selected' : '' }}>Featured</option>
                <option value="0" {{ request('featured') == '0' ? 'selected' : '' }}>Not Featured</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">
                <i class="fas fa-search"></i>
            </button>
            <a href="{{ route('admin.properties.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
    <form id="bulkActionForm" method="POST" action="{{ route('admin.properties.bulk-action') }}">
        @csrf
        <table class="min-w-full mobile-card-table">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price/Night</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Featured</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($properties as $property)
                <tr>
                    <td class="px-6 py-4">
                        <input type="checkbox" name="property_ids[]" value="{{ $property->id }}" class="property-checkbox rounded border-gray-300">
                    </td>
                    <td class="px-6 py-4" data-label="Title">{{ $property->title }}</td>
                    <td class="px-6 py-4" data-label="Location">{{ $property->city }}, {{ $property->state }}</td>
                    <td class="px-6 py-4" data-label="Price">₦{{ number_format($property->price_per_night) }}</td>
                    <td class="px-6 py-4" data-label="Status">
                        <span class="px-2 py-1 text-xs rounded-full 
                            {{ $property->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($property->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4" data-label="Featured">
                    {{ $property->is_featured ? 'Yes' : 'No' }}
                </td>
                <td class="px-6 py-4" data-label="Actions">
                    <a href="{{ route('admin.properties.show', $property->id) }}" class="text-primary hover:underline mr-3">View</a>
                    <a href="{{ route('admin.properties.edit', $property->id) }}" class="text-blue-600 hover:underline mr-3">Edit</a>
                    <form action="{{ route('admin.properties.destroy', $property->id) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Are you sure you want to delete this property?')">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No properties found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </form>
</div>

{{-- Bulk Actions Toolbar --}}
<div id="bulkActionsToolbar" class="hidden fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-primary text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-4 z-50">
    <span id="selectedCount" class="font-medium">0 selected</span>
    <div class="flex gap-2">
        <button type="button" onclick="bulkAction('active')" class="bg-green-600 px-3 py-1 rounded hover:bg-green-700">Set Active</button>
        <button type="button" onclick="bulkAction('inactive')" class="bg-gray-600 px-3 py-1 rounded hover:bg-gray-700">Set Inactive</button>
        <button type="button" onclick="bulkAction('featured')" class="bg-accent px-3 py-1 rounded hover:bg-yellow-700">Set Featured</button>
        <button type="button" onclick="bulkAction('delete')" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Delete</button>
    </div>
    <button type="button" onclick="clearSelection()" class="text-white hover:text-gray-300">
        <i class="fas fa-times"></i>
    </button>
</div>

@push('scripts')
<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.property-checkbox').forEach(cb => cb.checked = this.checked);
    updateToolbar();
});

document.querySelectorAll('.property-checkbox').forEach(cb => {
    cb.addEventListener('change', updateToolbar);
});

function updateToolbar() {
    const checked = document.querySelectorAll('.property-checkbox:checked').length;
    const toolbar = document.getElementById('bulkActionsToolbar');
    const countEl = document.getElementById('selectedCount');
    
    if (checked > 0) {
        toolbar.classList.remove('hidden');
        countEl.textContent = checked + ' selected';
    } else {
        toolbar.classList.add('hidden');
    }
}

function bulkAction(action) {
    const checked = document.querySelectorAll('.property-checkbox:checked');
    if (checked.length === 0) return;
    
    if (action === 'delete' && !confirm('Delete selected properties?')) return;
    
    const form = document.getElementById('bulkActionForm');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'action';
    input.value = action;
    form.appendChild(input);
    form.submit();
}

function clearSelection() {
    document.getElementById('selectAll').checked = false;
    document.querySelectorAll('.property-checkbox').forEach(cb => cb.checked = false);
    updateToolbar();
}
</script>
@endpush

@endsection