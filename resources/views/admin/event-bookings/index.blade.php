@extends('layouts.admin')

@section('title', 'Event Bookings')
@section('page_title', 'Event Bookings')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Event Bookings</h2>
            <p class="text-gray-500 text-sm">Manage all event space bookings</p>
        </div>
        <a href="{{ route('admin.event-spaces.index') }}" class="px-5 py-2.5 bg-[#0B2D5B] text-white font-bold rounded-lg hover:bg-[#123E7A] transition">
            <i class="fas fa-cogs mr-2"></i>Manage Event Spaces
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <select name="status" class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
            <input type="text" name="search" placeholder="Search events..." value="{{ request('search') }}"
                class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
            <button type="submit" class="px-5 py-2 bg-[#E11D2E] text-white font-bold rounded-lg hover:bg-[#C21627]">
                Filter
            </button>
        </form>
    </div>

    @if($bookings->isEmpty())
    <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
        <p class="text-gray-500">No bookings found</p>
    </div>
    @else
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Event</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Customer</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Space</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Guests</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($bookings as $booking)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-800">{{ $booking->event_name }}</p>
                        <p class="text-xs text-gray-500">{{ EventBooking::eventTypes()[$booking->event_type] }}</p>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $booking->user->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $booking->eventSpace->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $booking->event_date->format('M j, Y') }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $booking->num_guests }}
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-gray-800">
                        ₦{{ number_format($booking->total_price, 2) }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-medium 
                            @if($booking->status === 'confirmed') bg-green-100 text-green-700
                            @elseif($booking->status === 'pending') bg-yellow-100 text-yellow-700
                            @elseif($booking->status === 'cancelled') bg-red-100 text-red-700
                            @else bg-gray-100 text-gray-700 @endif">
                            {{ ucfirst($booking->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.event-bookings.show', $booking) }}" class="text-[#0B2D5B] hover:text-[#E11D2E] font-medium text-sm">
                            View <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex justify-center">
        {{ $bookings->links() }}
    </div>
    @endif
</div>
@endsection