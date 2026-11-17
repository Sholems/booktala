@extends('layouts.admin')

@section('title', 'Booking Details')
@section('page_title', 'Event Booking')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-800">{{ $booking->event_name }}</h2>
                <p class="text-gray-500 text-sm">{{ $booking->eventSpace->name ?? 'N/A' }}</p>
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
        <!-- Customer Info -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Customer</h3>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-[#E11D2E] rounded-full flex items-center justify-center text-white font-bold">
                    {{ substr($booking->user->name ?? 'U', 0, 1) }}
                </div>
                <div>
                    <p class="font-medium text-gray-800">{{ $booking->user->name ?? 'N/A' }}</p>
                    <p class="text-sm text-gray-500">{{ $booking->user->email ?? '' }}</p>
                </div>
            </div>
        </div>

        <!-- Event Details -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Event Details</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Event Type:</span> <span class="font-medium">{{ EventBooking::eventTypes()[$booking->event_type] }}</span></div>
                <div><span class="text-gray-500">Guests:</span> <span class="font-medium">{{ $booking->num_guests }}</span></div>
                <div><span class="text-gray-500">Date:</span> <span class="font-medium">{{ $booking->event_date->format('M j, Y') }}</span></div>
                <div><span class="text-gray-500">Time:</span> <span class="font-medium">{{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}</span></div>
            </div>
            @if($booking->notes)
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-gray-500 text-sm">Notes:</p>
                <p class="text-gray-800">{{ $booking->notes }}</p>
            </div>
            @endif
        </div>

        <!-- Pricing -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Payment</h3>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Total Amount</span>
                <span class="text-2xl font-black text-[#0B2D5B]">₦{{ number_format($booking->total_price, 2) }}</span>
            </div>
        </div>

        <!-- Status Update -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Update Status</h3>
            <form method="POST" action="{{ route('admin.event-bookings.update', $booking) }}" class="flex gap-4">
                @csrf
                @method('PATCH')
                <select name="status" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
                    @foreach(EventBooking::statuses() as $key => $label)
                    <option value="{{ $key }}" {{ $booking->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-5 py-2 bg-[#E11D2E] text-white font-bold rounded-lg hover:bg-[#C21627]">
                    Update
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 flex gap-4">
        <a href="{{ route('admin.event-bookings.index') }}" class="px-6 py-3 border border-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>
</div>
@endsection