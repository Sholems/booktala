@extends('layouts.admin')
@section('page_title', 'Hotel Amenities')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Hotel Amenities</h2>
    <a href="{{ route('admin.hotel-amenities.create') }}" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-accent/90">
        <i class="fas fa-plus mr-2"></i>Add Amenity
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($amenities as $amenity)
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                <i class="fas {{ $amenity->icon ?? 'fa-concierge-bell' }} text-primary text-xl"></i>
            </div>
            <span class="px-3 py-1 text-xs rounded-full {{ $amenity->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                {{ $amenity->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
        <h3 class="text-lg font-bold mb-2">{{ $amenity->name }}</h3>
        <p class="text-sm text-gray-600 mb-3">{{ $amenity->description }}</p>
        <div class="flex items-center text-sm text-gray-500 mb-4">
            <span class="mr-4"><i class="fas fa-tag mr-1"></i>{{ ucfirst($amenity->category) }}</span>
            @if($amenity->opening_time)
            <span><i class="fas fa-clock mr-1"></i>{{ \Carbon\Carbon::parse($amenity->opening_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($amenity->closing_time)->format('g:i A') }}</span>
            @endif
        </div>
        <div class="flex justify-end space-x-2 pt-4 border-t">
            <a href="{{ route('admin.hotel-amenities.edit', $amenity) }}" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-edit"></i>
            </a>
            <form method="POST" action="{{ route('admin.hotel-amenities.destroy', $amenity) }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure?')">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="col-span-3 text-center py-12">
        <p class="text-gray-500">No hotel amenities available. Create one to get started.</p>
    </div>
    @endforelse
</div>
@endsection