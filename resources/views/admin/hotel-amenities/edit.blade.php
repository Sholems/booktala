@extends('layouts.admin')
@section('page_title', 'Edit Hotel Amenity')
@section('content')
<h2 class="text-2xl font-bold mb-6">Edit Hotel Amenity</h2>

<form method="POST" action="{{ route('admin.hotel-amenities.update', $hotelAmenity) }}" class="bg-white rounded-lg shadow p-6">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Amenity Name *</label>
            <input type="text" name="name" required value="{{ $hotelAmenity->name }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Category *</label>
            <select name="category" required class="w-full border rounded px-3 py-2">
                <option value="">Select category</option>
                <option value="dining" {{ $hotelAmenity->category == 'dining' ? 'selected' : '' }}>Dining</option>
                <option value="wellness" {{ $hotelAmenity->category == 'wellness' ? 'selected' : '' }}>Wellness & Spa</option>
                <option value="fitness" {{ $hotelAmenity->category == 'fitness' ? 'selected' : '' }}>Fitness</option>
                <option value="recreation" {{ $hotelAmenity->category == 'recreation' ? 'selected' : '' }}>Recreation</option>
                <option value="business" {{ $hotelAmenity->category == 'business' ? 'selected' : '' }}>Business Services</option>
                <option value="other" {{ $hotelAmenity->category == 'other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Font Awesome Icon</label>
            <input type="text" name="icon" value="{{ $hotelAmenity->icon }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Location</label>
            <input type="text" name="location" value="{{ $hotelAmenity->location }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Opening Time</label>
            <input type="time" name="opening_time" value="{{ $hotelAmenity->opening_time }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Closing Time</label>
            <input type="time" name="closing_time" value="{{ $hotelAmenity->closing_time }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-4 md:col-span-2">
            <label class="block text-sm font-medium mb-2">Description</label>
            <textarea name="description" rows="3" class="w-full border rounded px-3 py-2">{{ $hotelAmenity->description }}</textarea>
        </div>
        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ $hotelAmenity->is_active ? 'checked' : '' }} class="mr-2">
                <span class="text-sm font-medium">Active</span>
            </label>
        </div>
        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="is_premium" value="1" {{ $hotelAmenity->is_premium ? 'checked' : '' }} class="mr-2">
                <span class="text-sm font-medium">Premium (Extra Charges)</span>
            </label>
        </div>
    </div>
    <div class="flex justify-end mt-6">
        <a href="{{ route('admin.hotel-amenities.index') }}" class="px-6 py-2 border rounded mr-2">Cancel</a>
        <button type="submit" class="bg-accent text-white px-6 py-2 rounded hover:bg-accent/90">Update</button>
    </div>
</form>
@endsection