@extends('layouts.admin')
@section('page_title', 'Add Room Type')
@section('content')
<h2 class="text-2xl font-bold mb-6">Add Room Type</h2>

<form method="POST" action="{{ route('admin.room-types.store') }}" class="bg-white rounded-lg shadow p-6">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Room Type Name *</label>
            <input type="text" name="name" required class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Bed Type</label>
            <select name="bed_type" class="w-full border rounded px-3 py-2">
                <option value="">Select bed type</option>
                <option value="single">Single Bed</option>
                <option value="double">Double Bed</option>
                <option value="twin">Twin Beds</option>
                <option value="queen">Queen Bed</option>
                <option value="king">King Bed</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Base Price (per night) *</label>
            <input type="number" name="base_price" required class="w-full border rounded px-3 py-2" placeholder="50000">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Weekend Price</label>
            <input type="number" name="weekend_price" class="w-full border rounded px-3 py-2" placeholder="60000">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Price per Week</label>
            <input type="number" name="price_per_week" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Price per Month</label>
            <input type="number" name="price_per_month" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Number of Rooms *</label>
            <input type="number" name="total_rooms" required value="1" min="1" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Room Size (sq ft)</label>
            <input type="number" name="room_size" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Max Adults *</label>
            <input type="number" name="max_adults" required value="2" min="1" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Max Children</label>
            <input type="number" name="max_children" value="0" min="0" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Floor</label>
            <input type="number" name="floor" value="1" min="1" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Building</label>
            <input type="text" name="building" class="w-full border rounded px-3 py-2" placeholder="Main Building">
        </div>
        <div class="mb-4 md:col-span-2">
            <label class="block text-sm font-medium mb-2">Short Description</label>
            <input type="text" name="short_description" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4 md:col-span-2">
            <label class="block text-sm font-medium mb-2">Full Description</label>
            <textarea name="description" rows="4" class="w-full border rounded px-3 py-2"></textarea>
        </div>
        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" checked class="mr-2">
                <span class="text-sm font-medium">Active</span>
            </label>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Sort Order</label>
            <input type="number" name="sort_order" value="0" class="w-full border rounded px-3 py-2">
        </div>
    </div>
    <div class="flex justify-end mt-6">
        <a href="{{ route('admin.room-types.index') }}" class="px-6 py-2 border rounded mr-2">Cancel</a>
        <button type="submit" class="bg-accent text-white px-6 py-2 rounded hover:bg-accent/90">Create Room Type</button>
    </div>
</form>
@endsection