@extends('layouts.admin')
@php
$isHotelMode = \App\Models\SystemSetting::get('hotel_mode', false);
@endphp
@section('page_title', 'Guest Report')
@section('content')
<div class="mb-6">
    <form method="GET" class="flex gap-4 items-end">
        <div>
            <label class="block text-sm font-medium mb-1">Start Date</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">End Date</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="border rounded px-3 py-2">
        </div>
        <button type="submit" class="bg-primary text-white px-4 py-2 rounded">Filter</button>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-4">New Guests by Month</h3>
        <div class="space-y-3">
            @forelse($newGuestsByMonth as $item)
            <div class="flex justify-between items-center">
                <span class="text-gray-600">{{ \Carbon\Carbon::create($item->year, $item->month)->format('F Y') }}</span>
                <span class="font-medium">{{ $item->count }}</span>
            </div>
            @empty
            <p class="text-gray-500">No data available</p>
            @endforelse
        </div>
    </div>

    @if($isHotelMode)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-4">Loyalty Tier Distribution</h3>
        <div class="space-y-3">
            @forelse($loyaltyTiers as $tier)
            <div class="flex justify-between items-center">
                <span class="capitalize">{{ $tier->loyalty_tier }}</span>
                <span class="font-medium">{{ $tier->count }}</span>
            </div>
            @empty
            <p class="text-gray-500">No loyalty data</p>
            @endforelse
        </div>
    </div>
    @endif
</div>

@if($isHotelMode && count($topGuests) > 0)
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h3 class="text-lg font-bold mb-4">Top Guests by Spending</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Guest</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Stays</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Nights</th>
                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">Total Spent</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Tier</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topGuests as $guest)
                <tr class="border-t">
                    <td class="px-4 py-3">{{ $guest->name }}</td>
                    <td class="px-4 py-3 text-center">{{ $guest->guestProfile->total_stays ?? 0 }}</td>
                    <td class="px-4 py-3 text-center">{{ $guest->guestProfile->total_nights ?? 0 }}</td>
                    <td class="px-4 py-3 text-right">₦{{ number_format($guest->guestProfile->total_spent ?? 0) }}</td>
                    <td class="px-4 py-3 text-center capitalize">{{ $guest->guestProfile->loyalty_tier ?? 'bronze' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-bold mb-4">All Guests</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Name</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Email</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Bookings</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Joined</th>
                </tr>
            </thead>
            <tbody>
                @forelse($guests as $guest)
                <tr class="border-t">
                    <td class="px-4 py-3">{{ $guest->name }}</td>
                    <td class="px-4 py-3">{{ $guest->email }}</td>
                    <td class="px-4 py-3 text-center">{{ $guest->reservations_count }}</td>
                    <td class="px-4 py-3">{{ $guest->created_at->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-3 text-center text-gray-500">No guests found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $guests->links() }}
    </div>
</div>
@endsection