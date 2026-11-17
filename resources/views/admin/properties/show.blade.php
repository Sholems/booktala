@extends('layouts.admin')

@section('page_title', 'Property Details')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <h2 class="text-2xl font-bold">{{ $property->title }}</h2>
    <div class="flex gap-2">
        <a href="{{ route('admin.properties.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
            <i class="fas fa-arrow-left mr-2"></i>Back
        </a>
        <a href="{{ route('admin.properties.edit', $property->id) }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">
            <i class="fas fa-edit mr-2"></i>Edit
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="bg-gray-200 h-48 md:h-64">
                @if($property->images->first())
                <img src="{{ $property->images->first()->image_path }}" alt="{{ $property->title }}" class="w-full h-48 md:h-64 object-cover">
                @else
                <div class="w-full h-48 md:h-64 bg-gray-300 flex items-center justify-center">
                    <i class="fas fa-home text-4xl md:text-6xl text-gray-400"></i>
                </div>
                @endif
            </div>
            @if($property->images->count() > 1)
            <div class="p-4 flex gap-2 overflow-x-auto">
                @foreach($property->images as $image)
                <img src="{{ $image->image_path }}" class="w-20 h-16 object-cover rounded">
                @endforeach
            </div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="mb-4">
                <p class="text-gray-600"><i class="fas fa-map-marker-alt mr-2"></i>{{ $property->address }}, {{ $property->city }}, {{ $property->state }}</p>
            </div>
            
            <div class="flex gap-4 mb-6 overflow-x-auto">
                <div class="bg-gray-100 p-3 rounded-lg flex-1 text-center min-w-[80px]">
                    <i class="fas fa-bed text-xl text-primary mb-1"></i>
                    <p class="font-bold">{{ $property->bedrooms }}</p>
                    <p class="text-xs text-gray-600">Beds</p>
                </div>
                <div class="bg-gray-100 p-3 rounded-lg flex-1 text-center min-w-[80px]">
                    <i class="fas fa-bath text-xl text-primary mb-1"></i>
                    <p class="font-bold">{{ $property->bathrooms }}</p>
                    <p class="text-xs text-gray-600">Baths</p>
                </div>
                <div class="bg-gray-100 p-3 rounded-lg flex-1 text-center min-w-[80px]">
                    <i class="fas fa-user text-xl text-primary mb-1"></i>
                    <p class="font-bold">{{ $property->max_guests }}</p>
                    <p class="text-xs text-gray-600">Guests</p>
                </div>
                <div class="bg-gray-100 p-3 rounded-lg flex-1 text-center min-w-[80px]">
                    <i class="fas fa-toilet text-xl text-primary mb-1"></i>
                    <p class="font-bold">{{ $property->toilets }}</p>
                    <p class="text-xs text-gray-600">Toilets</p>
                </div>
            </div>

            <div class="mb-6">
                <h3 class="text-lg font-bold mb-2">Description</h3>
                <p class="text-gray-700">{{ $property->description }}</p>
            </div>

            @if($property->amenities->count() > 0)
            <div class="mb-6">
                <h3 class="text-lg font-bold mb-2">Amenities</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach($property->amenities as $amenity)
                    <div class="flex items-center gap-2">
                        <i class="fas {{ $amenity->icon ?? 'fa-check' }} text-accent"></i>
                        <span>{{ $amenity->name }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($property->house_rules)
            <div class="mb-6">
                <h3 class="text-lg font-bold mb-2">House Rules</h3>
                <p class="text-gray-700">{{ $property->house_rules }}</p>
            </div>
            @endif

            @if($property->cancellation_policy)
            <div class="mb-6">
                <h3 class="text-lg font-bold mb-2">Cancellation Policy</h3>
                <p class="text-gray-700">{{ $property->cancellation_policy }}</p>
            </div>
            @endif
        </div>
    </div>

    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-bold mb-4">Pricing</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Price per night</span>
                    <span class="font-bold">₦{{ number_format($property->price_per_night) }}</span>
                </div>
                @if($property->weekly_discount > 0)
                <div class="flex justify-between">
                    <span class="text-gray-600">Weekly discount</span>
                    <span class="text-green-600">{{ $property->weekly_discount }}%</span>
                </div>
                @endif
                @if($property->monthly_discount > 0)
                <div class="flex justify-between">
                    <span class="text-gray-600">Monthly discount</span>
                    <span class="text-green-600">{{ $property->monthly_discount }}%</span>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-bold mb-4">Status</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Status</span>
                    <span class="px-2 py-1 text-xs rounded-full {{ $property->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($property->status) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Featured</span>
                    <span>{{ $property->is_featured ? 'Yes' : 'No' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Instant Book</span>
                    <span>{{ $property->instant_booking ? 'Yes' : 'No' }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">SEO</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-gray-600 block">Meta Title</span>
                    <span>{{ $property->meta_title ?? 'Not set' }}</span>
                </div>
                <div>
                    <span class="text-gray-600 block">Meta Description</span>
                    <span>{{ Str::limit($property->meta_description, 50) ?? 'Not set' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection