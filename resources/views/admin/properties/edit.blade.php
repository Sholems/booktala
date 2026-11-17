@extends('layouts.admin')

@section('page_title', 'Edit Property')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h2 class="text-2xl font-bold">Edit Property</h2>
    <a href="{{ route('admin.properties.index') }}" class="text-primary hover:underline">&larr; Back to Properties</a>
</div>

<form method="POST" action="{{ route('admin.properties.update', $property) }}" class="bg-white rounded-lg shadow p-6" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium mb-2">Title</label>
            <input type="text" name="title" value="{{ $property->title }}" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Slug</label>
            <input type="text" name="slug" value="{{ $property->slug }}" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">City</label>
            <input type="text" name="city" value="{{ $property->city }}" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">State</label>
            <input type="text" name="state" value="{{ $property->state }}" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Price per Night (₦)</label>
            <input type="number" name="price_per_night" value="{{ $property->price_per_night }}" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Weekly Price (₦)</label>
            <input type="number" name="price_per_week" value="{{ $property->price_per_week }}" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Monthly Price (₦)</label>
            <input type="number" name="price_per_month" value="{{ $property->price_per_month }}" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Max Guests</label>
            <input type="number" name="max_guests" value="{{ $property->max_guests }}" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Bedrooms</label>
            <input type="number" name="bedrooms" value="{{ $property->bedrooms }}" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Bathrooms</label>
            <input type="number" name="bathrooms" value="{{ $property->bathrooms }}" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Toilets</label>
            <input type="number" name="toilets" value="{{ $property->toilets }}" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Min Nights</label>
            <input type="number" name="min_nights" value="{{ $property->min_nights ?? 1 }}" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Max Nights</label>
            <input type="number" name="max_nights" value="{{ $property->max_nights ?? 30 }}" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Status</label>
            <select name="status" class="w-full border rounded px-3 py-2">
                <option value="active" {{ $property->status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $property->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="maintenance" {{ $property->status == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Featured</label>
            <select name="is_featured" class="w-full border rounded px-3 py-2">
                <option value="0" {{ !$property->is_featured ? 'selected' : '' }}>No</option>
                <option value="1" {{ $property->is_featured ? 'selected' : '' }}>Yes</option>
            </select>
        </div>
    </div>
    
    @include('admin.properties._ownership', ['property' => $property ?? null])

    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Address</label>
        <textarea name="address" class="w-full border rounded px-3 py-2" rows="2" required>{{ $property->address }}</textarea>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Short Description</label>
        <textarea name="short_description" class="w-full border rounded px-3 py-2" rows="2">{{ $property->short_description }}</textarea>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Description</label>
        <textarea name="description" class="w-full border rounded px-3 py-2" rows="4" required>{{ $property->description }}</textarea>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">House Rules</label>
        <textarea name="house_rules" class="w-full border rounded px-3 py-2" rows="3">{{ $property->house_rules }}</textarea>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Cancellation Policy</label>
        <textarea name="cancellation_policy" class="w-full border rounded px-3 py-2" rows="3">{{ $property->cancellation_policy }}</textarea>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Amenities</label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-2">
            @forelse($amenities as $category => $categoryAmenities)
                @foreach($categoryAmenities as $amenity)
                <label class="flex items-center gap-2 p-2 border rounded cursor-pointer hover:bg-gray-50 {{ $property->amenities->contains($amenity->id) ? 'bg-blue-50 border-blue-300' : '' }}">
                    <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}" {{ $property->amenities->contains($amenity->id) ? 'checked' : '' }} class="rounded">
                    <span class="text-sm">{{ $amenity->name }}</span>
                </label>
                @endforeach
            @empty
                <p class="text-gray-500 col-span-4">No amenities available</p>
            @endforelse
        </div>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Current Images</label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
            @forelse($property->images as $image)
            <div class="relative border rounded p-2">
                <img src="{{ $image->image_path }}" class="w-full h-24 object-cover rounded">
                <div class="mt-2 flex items-center gap-2">
                    <label class="flex items-center gap-1 text-sm">
                        <input type="radio" name="primary_image" value="{{ $image->id }}" {{ $image->is_primary ? 'checked' : '' }}>
                        Primary
                    </label>
                    <label class="flex items-center gap-1 text-sm">
                        <input type="checkbox" name="delete_images[]" value="{{ $image->id }}">
                        Delete
                    </label>
                </div>
            </div>
            @empty
            <p class="text-gray-500 col-span-4">No images uploaded</p>
            @endforelse
        </div>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Add More Images</label>
        <input type="file" name="images[]" multiple accept="image/*" class="w-full border rounded px-3 py-2">
    </div>
    
    <div class="mt-6">
        <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-primary/90">Update Property</button>
    </div>
</form>
@endsection
