@extends('layouts.customer')

@section('title', 'Event Booking Details')
@section('page_title', 'Event Booking')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-800">{{ $booking->event_name }}</h2>
                <p class="text-gray-500 text-sm mt-1">{{ $booking->eventSpace->name ?? 'N/A' }}</p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-bold 
                @if($booking->status === 'confirmed') bg-green-100 text-green-700
                @elseif($booking->status === 'pending') bg-yellow-100 text-yellow-700
                @elseif($booking->status === 'cancelled') bg-red-100 text-red-700
                @else bg-gray-100 text-gray-700 @endif">
                {{ ucfirst($booking->status) }}
            </span>
        </div>
    </div>

    <div class="grid gap-6">
        <!-- Event Details -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Event Details</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Event Type:</span> <span class="font-medium">{{ EventBooking::eventTypes()[$booking->event_type] }}</span></div>
                <div><span class="text-gray-500">Guests:</span> <span class="font-medium">{{ $booking->num_guests }}</span></div>
                <div><span class="text-gray-500">Date:</span> <span class="font-medium">{{ $booking->event_date->format('M j, Y') }}</span></div>
                <div><span class="text-gray-500">Time:</span> <span class="font-medium">{{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}</span></div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Pricing</h3>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Total Amount</span>
                <span class="text-2xl font-black text-[#0B2D5B]">₦{{ number_format($booking->total_price, 2) }}</span>
            </div>
        </div>

        <!-- Notes -->
        @if($booking->notes)
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-2">Notes</h3>
            <p class="text-gray-600">{{ $booking->notes }}</p>
        </div>
        @endif
    </div>

    <div class="mt-6 flex gap-4">
        <a href="{{ route('customer.event-bookings.index') }}" class="px-6 py-3 border border-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
        @if(in_array($booking->status, ['pending', 'confirmed']))
        <form action="{{ route('customer.event-bookings.cancel', $booking) }}" method="POST" onsubmit="return confirm('Are you sure?')">
            @csrf
            <button type="submit" class="px-6 py-3 border border-red-200 text-red-600 font-bold rounded-lg hover:bg-red-50 transition">
                <i class="fas fa-times mr-2"></i> Cancel Booking
            </button>
        </form>
        @endif
    </div>
</div>
@endsection