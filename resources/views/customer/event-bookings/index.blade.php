@extends('layouts.customer')

@section('title', 'Event Bookings')
@section('page_title', 'Event Bookings')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Your Event Bookings</h2>
            <p class="text-gray-500 text-sm">View and manage your event space bookings</p>
        </div>
        <a href="{{ route('customer.event-bookings.create') }}" class="px-5 py-2.5 bg-accent text-white font-bold rounded-lg hover:bg-accent-dark transition">
            <i class="fas fa-plus mr-2"></i>Book Event Space
        </a>
    </div>

    @if($bookings->isEmpty())
    <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-calendar-day text-gray-400 text-2xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">No Event Bookings</h3>
        <p class="text-gray-500 mb-6">You haven't booked any event spaces yet.</p>
        <a href="{{ route('customer.event-bookings.create') }}" class="inline-block px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary-light transition">
            Book Your First Event
        </a>
    </div>
    @else
    <div class="grid gap-4">
        @foreach($bookings as $booking)
        <div class="bg-white rounded-xl border border-gray-100 p-6 flex flex-col md:flex-row md:items-center gap-4">
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-800">{{ $booking->event_name }}</h3>
                <p class="text-gray-500 text-sm mt-1">
                    <i class="fas fa-map-marker-alt mr-2"></i>
                    {{ $booking->eventSpace->name ?? 'N/A' }}
                </p>
                <p class="text-gray-500 text-sm">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    {{ $booking->event_date->format('M j, Y') }} | {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
                </p>
                <p class="text-gray-500 text-sm">
                    <i class="fas fa-users mr-2"></i>
                    {{ $booking->num_guests }} guests
                </p>
            </div>
            <div class="flex flex-col items-start md:items-end gap-2">
                <span class="px-3 py-1 rounded-full text-sm font-medium 
                    @if($booking->status === 'confirmed') bg-green-100 text-green-700
                    @elseif($booking->status === 'pending') bg-yellow-100 text-yellow-700
                    @elseif($booking->status === 'cancelled') bg-red-100 text-red-700
                    @else bg-gray-100 text-gray-700 @endif">
                    {{ ucfirst($booking->status) }}
                </span>
                <p class="font-bold text-gray-800">₦{{ number_format($booking->total_price, 2) }}</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('customer.event-bookings.show', $booking) }}" class="px-4 py-2 border border-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition text-center">
                    View Details
                </a>
                @if(in_array($booking->status, ['pending', 'confirmed']))
                <form action="{{ route('customer.event-bookings.cancel', $booking) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 border border-red-200 text-red-600 font-medium rounded-lg hover:bg-red-50 transition">
                        Cancel
                    </button>
                </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="flex justify-center">
        {{ $bookings->links() }}
    </div>
    @endif
</div>
@endsection