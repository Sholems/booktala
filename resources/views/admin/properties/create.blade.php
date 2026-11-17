@extends('layouts.admin')

@section('page_title', 'Create Property')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold">Add New Property</h2>
</div>

<form method="POST" action="{{ route('admin.properties.store') }}" class="bg-white rounded-lg shadow p-6" enctype="multipart/form-data">
    @csrf
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium mb-2">Title</label>
            <input type="text" name="title" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">City</label>
            <input type="text" name="city" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">State</label>
            <input type="text" name="state" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Price per Night (₦)</label>
            <input type="number" name="price_per_night" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Weekly Price (₦)</label>
            <input type="number" name="price_per_week" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Monthly Price (₦)</label>
            <input type="number" name="price_per_month" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Max Guests</label>
            <input type="number" name="max_guests" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Bedrooms</label>
            <input type="number" name="bedrooms" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Bathrooms</label>
            <input type="number" name="bathrooms" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Toilets</label>
            <input type="number" name="toilets" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Min Nights</label>
            <input type="number" name="min_nights" value="1" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Max Nights</label>
            <input type="number" name="max_nights" value="30" class="w-full border rounded px-3 py-2">
        </div>
    </div>
    
    @include('admin.properties._ownership', ['property' => $property ?? null])

    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Address</label>
        <textarea name="address" class="w-full border rounded px-3 py-2" rows="2" required></textarea>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Short Description</label>
        <textarea name="short_description" class="w-full border rounded px-3 py-2" rows="2"></textarea>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Description</label>
        <textarea name="description" class="w-full border rounded px-3 py-2" rows="4" required></textarea>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">House Rules</label>
        <textarea name="house_rules" class="w-full border rounded px-3 py-2" rows="3"></textarea>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Cancellation Policy</label>
        <textarea name="cancellation_policy" class="w-full border rounded px-3 py-2" rows="3"></textarea>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Amenities</label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-2">
            @forelse($amenities as $category => $categoryAmenities)
                @foreach($categoryAmenities as $amenity)
                <label class="flex items-center gap-2 p-2 border rounded cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}" class="rounded">
                    <span class="text-sm">{{ $amenity->name }}</span>
                </label>
                @endforeach
            @empty
                <p class="text-gray-500 col-span-4">No amenities available</p>
            @endforelse
        </div>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Images</label>
        <input type="file" name="images[]" multiple accept="image/*" class="w-full border rounded px-3 py-2">
        <p class="text-sm text-gray-500 mt-1">Select multiple images (first image will be primary)</p>
    </div>
    
    <div class="mt-6">
        <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-primary/90">Create Property</button>
    </div>
</form>
@endsection
