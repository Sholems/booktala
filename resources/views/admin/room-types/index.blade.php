@extends('layouts.admin')
@section('page_title', 'Room Types')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Room Types</h2>
    <a href="{{ route('admin.room-types.create') }}" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-accent/90">
        <i class="fas fa-plus mr-2"></i>Add Room Type
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($roomTypes as $roomType)
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-xl font-bold">{{ $roomType->name }}</h3>
                <span class="px-3 py-1 text-sm rounded-full {{ $roomType->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $roomType->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <p class="text-gray-600 text-sm mb-4">{{ $roomType->short_description }}</p>
            <div class="flex justify-between items-center text-sm text-gray-600 mb-4">
                <span><i class="fas fa-door-open mr-1"></i>{{ $roomType->total_rooms }} rooms</span>
                <span><i class="fas fa-user mr-1"></i>Max {{ $roomType->max_adults }} adults</span>
            </div>
            <div class="flex justify-between items-center pt-4 border-t">
                <div>
                    <span class="text-2xl font-bold text-primary">₦{{ number_format($roomType->base_price) }}</span>
                    <span class="text-gray-500 text-sm">/night</span>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('admin.room-types.rooms', $roomType) }}" class="text-primary hover:text-accent" title="Manage Rooms">
                        <i class="fas fa-bed"></i>
                    </a>
                    <a href="{{ route('admin.room-types.edit', $roomType) }}" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form method="POST" action="{{ route('admin.room-types.destroy', $roomType) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                @php $available = $roomType->rooms()->where('status', 'available')->count(); @endphp
                <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded">{{ $available }} Available</span>
                @php $occupied = $roomType->rooms()->where('status', 'occupied')->count(); @endphp
                @if($occupied > 0)
                <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded">{{ $occupied }} Occupied</span>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-3 text-center py-12">
        <p class="text-gray-500">No room types available. Create one to get started.</p>
    </div>
    @endforelse
</div>
@endsection