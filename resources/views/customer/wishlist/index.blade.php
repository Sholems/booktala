@extends('layouts.customer')

@section('page_title', 'My Wishlist')

@section('content')
<div class="container mx-auto px-6 py-8">
    <h1 class="text-2xl font-bold mb-6">My Wishlist</h1>
    
    @if($wishlists->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($wishlists as $wishlist)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            @if($wishlist->property->images->count() > 0)
            <img src="{{ $wishlist->property->images->first()->image_path }}" alt="{{ $wishlist->property->name }}" class="w-full h-48 object-cover">
            @else
            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                <span class="text-gray-500">No Image</span>
            </div>
            @endif
            <div class="p-4">
                <h3 class="text-lg font-semibold mb-2">
                    <a href="{{ route('properties.show', $wishlist->property->slug) }}" class="hover:text-primary">
                        {{ $wishlist->property->name }}
                    </a>
                </h3>
                <p class="text-gray-600 text-sm mb-2">{{ $wishlist->property->address }}</p>
                <div class="flex justify-between items-center">
                    <span class="text-lg font-bold text-primary">₦{{ number_format($wishlist->property->price_per_night) }}/night</span>
                    <form action="{{ route('customer.wishlist.destroy', $wishlist->property) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800">Remove</button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    
    <div class="mt-6">
        {{ $wishlists->links() }}
    </div>
    @else
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-600 mb-4">Your wishlist is empty</p>
        <a href="{{ route('properties.index') }}" class="text-primary hover:underline">Browse apartments →</a>
    </div>
    @endif
</div>
@endsection
