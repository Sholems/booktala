@extends('layouts.app')

@section('title', 'Our Apartments - BookTala')

@push('styles')
<style>
    @media (max-width: 1023px) {
        .filters-sidebar { display: none; }
        .filters-sidebar.open { display: block; position: fixed; inset: 0; z-index: 50; background: white; overflow-y: auto; padding: 1rem; }
    }
</style>
@endpush

@section('content')
<section class="py-6 md:py-12 bg-primary text-white">
    <div class="container mx-auto px-4 md:px-6">
        <h1 class="text-2xl md:text-4xl font-bold mb-1 md:mb-2">Our Apartments</h1>
        <p class="text-base md:text-xl">Find your perfect accommodation</p>
    </div>
</section>

<section class="py-6 md:py-12">
    <div class="container mx-auto px-4 md:px-6">
        <button class="md:hidden w-full bg-white p-4 rounded-lg shadow mb-4 flex items-center justify-between" onclick="document.querySelector('.filters-sidebar').classList.toggle('open')">
            <span class="font-bold">Filters</span>
            <i class="fas fa-filter"></i>
        </button>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 md:gap-8">
            <div class="filters-sidebar lg:col-span-1">
                <div class="bg-white p-4 md:p-6 rounded-lg shadow">
                    <div class="flex justify-between items-center mb-4 lg:hidden">
                        <h3 class="text-lg font-bold">Filters</h3>
                        <button onclick="document.querySelector('.filters-sidebar').classList.toggle('open')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form action="{{ route('properties.index') }}" method="GET">
                        <div class="mb-3 md:mb-4">
                            <label class="block text-gray-700 text-sm mb-1 md:mb-2">Location / City</label>
                            <input type="text" name="city" value="{{ request('city') }}" class="w-full px-3 md:px-4 py-2 border border-gray-300 rounded-lg" placeholder="City or area">
                        </div>

                        <div class="mb-3 md:mb-4">
                            <label class="block text-gray-700 text-sm mb-1 md:mb-2">Property Type</label>
                            <select name="property_type" class="w-full px-3 md:px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">All Types</option>
                                <option value="apartment" {{ request('property_type') === 'apartment' ? 'selected' : '' }}>Apartment</option>
                                <option value="villa" {{ request('property_type') === 'villa' ? 'selected' : '' }}>Villa</option>
                                <option value="studio" {{ request('property_type') === 'studio' ? 'selected' : '' }}>Studio</option>
                                <option value="penthouse" {{ request('property_type') === 'penthouse' ? 'selected' : '' }}>Penthouse</option>
                                <option value="townhouse" {{ request('property_type') === 'townhouse' ? 'selected' : '' }}>Townhouse</option>
                            </select>
                        </div>

                        <div class="mb-3 md:mb-4">
                            <label class="block text-gray-700 text-sm mb-1 md:mb-2">Price Range (per night)</label>
                            <div class="flex gap-2">
                                <input type="number" name="min_price" value="{{ request('min_price') }}" class="w-full px-3 md:px-4 py-2 border border-gray-300 rounded-lg" placeholder="Min">
                                <input type="number" name="max_price" value="{{ request('max_price') }}" class="w-full px-3 md:px-4 py-2 border border-gray-300 rounded-lg" placeholder="Max">
                            </div>
                        </div>

                        <div class="mb-3 md:mb-4">
                            <label class="block text-gray-700 text-sm mb-1 md:mb-2">Guests</label>
                            <select name="guests" class="w-full px-3 md:px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Any number</option>
                                <option value="1" {{ request('guests') == 1 ? 'selected' : '' }}>1+ Guest</option>
                                <option value="2" {{ request('guests') == 2 ? 'selected' : '' }}>2+ Guests</option>
                                <option value="3" {{ request('guests') == 3 ? 'selected' : '' }}>3+ Guests</option>
                                <option value="4" {{ request('guests') == 4 ? 'selected' : '' }}>4+ Guests</option>
                                <option value="5" {{ request('guests') == 5 ? 'selected' : '' }}>5+ Guests</option>
                                <option value="6" {{ request('guests') == 6 ? 'selected' : '' }}>6+ Guests</option>
                            </select>
                        </div>

                        <div class="mb-3 md:mb-4">
                            <label class="block text-gray-700 text-sm mb-1 md:mb-2">Bedrooms</label>
                            <select name="bedrooms" class="w-full px-3 md:px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Any</option>
                                <option value="1" {{ request('bedrooms') == 1 ? 'selected' : '' }}>1+ Bedroom</option>
                                <option value="2" {{ request('bedrooms') == 2 ? 'selected' : '' }}>2+ Bedrooms</option>
                                <option value="3" {{ request('bedrooms') == 3 ? 'selected' : '' }}>3+ Bedrooms</option>
                                <option value="4" {{ request('bedrooms') == 4 ? 'selected' : '' }}>4+ Bedrooms</option>
                                <option value="5" {{ request('bedrooms') == 5 ? 'selected' : '' }}>5+ Bedrooms</option>
                            </select>
                        </div>

                        <div class="mb-3 md:mb-4">
                            <label class="block text-gray-700 text-sm mb-1 md:mb-2">Bathrooms</label>
                            <select name="bathrooms" class="w-full px-3 md:px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Any</option>
                                <option value="1" {{ request('bathrooms') == 1 ? 'selected' : '' }}>1+ Bathroom</option>
                                <option value="2" {{ request('bathrooms') == 2 ? 'selected' : '' }}>2+ Bathrooms</option>
                                <option value="3" {{ request('bathrooms') == 3 ? 'selected' : '' }}>3+ Bathrooms</option>
                            </select>
                        </div>

                        <div class="mb-3 md:mb-4">
                            <label class="block text-gray-700 text-sm mb-2">Amenities</label>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                @foreach($amenities as $amenity)
                                <label class="flex items-center text-sm">
                                    <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}"
                                        {{ in_array($amenity->id, (array) request('amenities', [])) ? 'checked' : '' }}
                                        class="mr-2 rounded">
                                    {{ $amenity->name }}
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-3 md:mb-4">
                            <label class="block text-gray-700 text-sm mb-1 md:mb-2">Sort By</label>
                            <select name="sort" class="w-full px-3 md:px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest First</option>
                                <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                                <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                                <option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>Highest Rated</option>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 bg-primary text-white py-2 rounded-lg hover:bg-primary/90">Apply</button>
                            <a href="{{ route('properties.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-3">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-gray-700 text-sm md:text-base">
                        Showing {{ $properties->count() }} of {{ $properties->total() }} apartments
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    @forelse($properties as $property)
                    <article class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                        <div class="relative h-44 md:h-52">
                            @if($property->primaryImage)
                            <img src="{{ $property->primaryImage->image_path }}" alt="{{ $property->title }}" class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-home text-4xl text-gray-400"></i>
                            </div>
                            @endif

                            @if($property->is_featured)
                            <span class="absolute top-3 left-3 bg-accent text-white text-xs font-bold px-2 py-1 rounded">Featured</span>
                            @endif
                        </div>

                        <div class="p-4 md:p-5">
                            <h3 class="text-lg md:text-xl font-bold text-primary mb-1">{{ $property->title }}</h3>
                            <p class="text-sm text-gray-600 mb-3"><i class="fas fa-map-marker-alt mr-1"></i>{{ $property->city }}, {{ $property->state }}</p>

                            <div class="grid grid-cols-3 gap-2 text-xs md:text-sm text-gray-600 mb-3">
                                <span><i class="fas fa-bed mr-1"></i>{{ $property->bedrooms }} Bed</span>
                                <span><i class="fas fa-bath mr-1"></i>{{ $property->bathrooms }} Bath</span>
                                <span><i class="fas fa-user mr-1"></i>{{ $property->max_guests }} Guests</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-primary font-bold text-lg md:text-xl">&#8358;{{ number_format($property->price_per_night) }}</p>
                                    <p class="text-xs text-gray-500">per night</p>
                                </div>
                                <a href="{{ route('properties.show', $property->slug) }}" class="px-4 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/90">View Details</a>
                            </div>
                        </div>
                    </article>
                    @empty
                    <div class="md:col-span-2 bg-white rounded-lg shadow p-8 text-center">
                        <i class="fas fa-search text-4xl text-gray-400 mb-3"></i>
                        <h3 class="text-xl font-bold text-gray-700 mb-2">No apartments found</h3>
                        <p class="text-gray-600 mb-4">Try adjusting your filters to see more results.</p>
                        <a href="{{ route('properties.index') }}" class="inline-block px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Clear filters</a>
                    </div>
                    @endforelse
                </div>

                @if($properties->hasPages())
                <div class="mt-6 md:mt-8">
                    {{ $properties->withQueryString()->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
