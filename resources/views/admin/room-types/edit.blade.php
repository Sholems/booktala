@extends('layouts.admin')
@section('page_title', 'Edit Room Type')
@section('content')
<h2 class="text-2xl font-bold mb-6">Edit Room Type</h2>

<form method="POST" action="{{ route('admin.room-types.update', $roomType) }}" class="bg-white rounded-lg shadow p-6">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Room Type Name *</label>
            <input type="text" name="name" required value="{{ $roomType->name }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Bed Type</label>
            <select name="bed_type" class="w-full border rounded px-3 py-2">
                <option value="">Select bed type</option>
                <option value="single" {{ $roomType->bed_type == 'single' ? 'selected' : '' }}>Single Bed</option>
                <option value="double" {{ $roomType->bed_type == 'double' ? 'selected' : '' }}>Double Bed</option>
                <option value="twin" {{ $roomType->bed_type == 'twin' ? 'selected' : '' }}>Twin Beds</option>
                <option value="queen" {{ $roomType->bed_type == 'queen' ? 'selected' : '' }}>Queen Bed</option>
                <option value="king" {{ $roomType->bed_type == 'king' ? 'selected' : '' }}>King Bed</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Base Price (per night) *</label>
            <input type="number" name="base_price" required value="{{ $roomType->base_price }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Weekend Price</label>
            <input type="number" name="weekend_price" value="{{ $roomType->weekend_price }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Price per Week</label>
            <input type="number" name="price_per_week" value="{{ $roomType->price_per_week }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Price per Month</label>
            <input type="number" name="price_per_month" value="{{ $roomType->price_per_month }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Number of Rooms *</label>
            <input type="number" name="total_rooms" required value="{{ $roomType->total_rooms }}" min="1" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Room Size (sq ft)</label>
            <input type="number" name="room_size" value="{{ $roomType->room_size }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Max Adults *</label>
            <input type="number" name="max_adults" required value="{{ $roomType->max_adults }}" min="1" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Max Children</label>
            <input type="number" name="max_children" value="{{ $roomType->max_children }}" min="0" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Floor</label>
            <input type="number" name="floor" value="1" min="1" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Building</label>
            <input type="text" name="building" value="{{ $roomType->building }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4 md:col-span-2">
            <label class="block text-sm font-medium mb-2">Short Description</label>
            <input type="text" name="short_description" value="{{ $roomType->short_description }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4 md:col-span-2">
            <label class="block text-sm font-medium mb-2">Full Description</label>
            <textarea name="description" rows="4" class="w-full border rounded px-3 py-2">{{ $roomType->description }}</textarea>
        </div>
        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ $roomType->is_active ? 'checked' : '' }} class="mr-2">
                <span class="text-sm font-medium">Active</span>
            </label>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Sort Order</label>
            <input type="number" name="sort_order" value="{{ $roomType->sort_order }}" class="w-full border rounded px-3 py-2">
        </div>
    </div>
    <div class="flex justify-end mt-6">
        <a href="{{ route('admin.room-types.index') }}" class="px-6 py-2 border rounded mr-2">Cancel</a>
        <button type="submit" class="bg-accent text-white px-6 py-2 rounded hover:bg-accent/90">Update Room Type</button>
    </div>
</form>
@endsection