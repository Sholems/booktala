@extends('layouts.admin')
@section('page_title', 'Add Hotel Amenity')
@section('content')
<h2 class="text-2xl font-bold mb-6">Add Hotel Amenity</h2>

<form method="POST" action="{{ route('admin.hotel-amenities.store') }}" class="bg-white rounded-lg shadow p-6">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Amenity Name *</label>
            <input type="text" name="name" required class="w-full border rounded px-3 py-2" placeholder="e.g., Swimming Pool">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Category *</label>
            <select name="category" required class="w-full border rounded px-3 py-2">
                <option value="">Select category</option>
                <option value="dining">Dining</option>
                <option value="wellness">Wellness & Spa</option>
                <option value="fitness">Fitness</option>
                <option value="recreation">Recreation</option>
                <option value="business">Business Services</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Font Awesome Icon</label>
            <input type="text" name="icon" class="w-full border rounded px-3 py-2" placeholder="e.g., fa-swimming-pool">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Location</label>
            <input type="text" name="location" class="w-full border rounded px-3 py-2" placeholder="e.g., Ground Floor">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Opening Time</label>
            <input type="time" name="opening_time" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Closing Time</label>
            <input type="time" name="closing_time" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4 md:col-span-2">
            <label class="block text-sm font-medium mb-2">Description</label>
            <textarea name="description" rows="3" class="w-full border rounded px-3 py-2"></textarea>
        </div>
        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" checked class="mr-2">
                <span class="text-sm font-medium">Active</span>
            </label>
        </div>
        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="is_premium" value="1" class="mr-2">
                <span class="text-sm font-medium">Premium (Extra Charges)</span>
            </label>
        </div>
    </div>
    <div class="flex justify-end mt-6">
        <a href="{{ route('admin.hotel-amenities.index') }}" class="px-6 py-2 border rounded mr-2">Cancel</a>
        <button type="submit" class="bg-accent text-white px-6 py-2 rounded hover:bg-accent/90">Create</button>
    </div>
</form>
@endsection