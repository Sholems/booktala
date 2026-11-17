@extends('layouts.admin')
@php
$isHotelMode = \App\Models\SystemSetting::get('hotel_mode', false);
$totalRooms = isset($totalRooms) ? $totalRooms : 0;
$availableRooms = isset($availableRooms) ? $availableRooms : 0;
$occupiedRooms = isset($occupiedRooms) ? $occupiedRooms : 0;
$occupancyRate = isset($occupancyRate) ? $occupancyRate : 0;
$totalProperties = isset($totalProperties) ? $totalProperties : 0;
$featuredProperties = isset($featuredProperties) ? $featuredProperties : 0;
$revenue = isset($revenue) ? $revenue : 0;
$monthlyRevenue = isset($monthlyRevenue) ? $monthlyRevenue : 0;
$revenueGrowth = isset($revenueGrowth) ? $revenueGrowth : 0;
$totalBookings = isset($totalBookings) ? $totalBookings : 0;
$confirmedBookings = isset($confirmedBookings) ? $confirmedBookings : 0;
$cancelledBookings = isset($cancelledBookings) ? $cancelledBookings : 0;
$roomTypeOccupancy = isset($roomTypeOccupancy) ? $roomTypeOccupancy : [];
$topProperties = isset($topProperties) ? $topProperties : [];
$guestStats = isset($guestStats) ? $guestStats : ['total' => 0, 'newThisMonth' => 0, 'withLoyalty' => 0, 'avgNights' => 0];
@endphp
@section('page_title', 'Reports & Analytics')
@section('content')
<div class="mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium mb-1">Start Date</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">End Date</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="border rounded px-3 py-2">
        </div>
        <button type="submit" class="bg-primary text-white px-4 py-2 rounded hover:bg-primary/90">Filter</button>
        <div class="ml-auto flex gap-2">
            <a href="{{ route('admin.reports.index') }}" class="px-4 py-2 border rounded hover:bg-gray-50">Today</a>
            <a href="{{ route('admin.reports.index', ['start_date' => now()->startOfMonth()->toDateString(), 'end_date' => now()->endOfMonth()->toDateString()]) }}" class="px-4 py-2 border rounded hover:bg-gray-50">This Month</a>
            <a href="{{ route('admin.reports.index', ['start_date' => now()->startOfYear()->toDateString(), 'end_date' => now()->toDateString()]) }}" class="px-4 py-2 border rounded hover:bg-gray-50">This Year</a>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ $isHotelMode ? 'Total Rooms' : 'Total Properties' }}</p>
                <p class="text-2xl font-bold">{{ $isHotelMode ? $totalRooms : $totalProperties }}</p>
            </div>
            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                <i class="fas fa-{{ $isHotelMode ? 'bed' : 'building' }} text-primary text-xl"></i>
            </div>
        </div>
        @if($isHotelMode)
        <div class="mt-4 text-sm text-gray-500">
            <span class="text-green-600">{{ $availableRooms }} available</span> | 
            <span class="text-yellow-600">{{ $occupiedRooms }} occupied</span>
        </div>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ $isHotelMode ? 'Occupancy Rate' : 'Featured Properties' }}</p>
                <p class="text-2xl font-bold">{{ $isHotelMode ? $occupancyRate . '%' : $featuredProperties }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-{{ $isHotelMode ? 'percentage' : 'star' }} text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Revenue</p>
                <p class="text-2xl font-bold">₦{{ number_format($revenue) }}</p>
            </div>
            <div class="w-12 h-12 bg-accent/10 rounded-full flex items-center justify-center">
                <i class="fas fa-naira-sign text-accent text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm">
            <span class="text-gray-500">This month: </span>
            <span class="font-medium">₦{{ number_format($monthlyRevenue) }}</span>
            <span class="ml-2 text-{{ $revenueGrowth >= 0 ? 'green' : 'red' }}-600">
                <i class="fas fa-{{ $revenueGrowth >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                {{ abs($revenueGrowth) }}%
            </span>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Bookings</p>
                <p class="text-2xl font-bold">{{ $totalBookings }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-500">
            <span class="text-green-600">{{ $confirmedBookings }} confirmed</span> |
            <span class="text-red-600">{{ $cancelledBookings }} cancelled</span>
        </div>
    </div>
</div>

@if($isHotelMode)
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h3 class="text-lg font-bold mb-4">Room Type Occupancy</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($roomTypeOccupancy as $type)
        @php $rate = $type['rate']; @endphp
        <div class="border rounded p-4">
            <div class="flex justify-between items-center mb-2">
                <span class="font-medium">{{ $type['name'] }}</span>
                <span class="text-sm text-gray-500">{{ $rate }}%</span>
            </div>
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-primary rounded-full" data-width="{{ $rate }}"></div>
            </div>
            <div class="mt-2 text-sm text-gray-500">
                {{ $type['occupied'] }}/{{ $type['total'] }} rooms
            </div>
        </div>
        @endforeach
    </div>
</div>
<script>
document.querySelectorAll('[data-width]').forEach(function(el) {
    el.style.width = el.getAttribute('data-width') + '%';
});
</script>
@else
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h3 class="text-lg font-bold mb-4">Top Properties</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Property</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Bookings</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topProperties as $property)
                <tr class="border-t">
                    <td class="px-4 py-3">{{ $property->title }}</td>
                    <td class="px-4 py-3">{{ $property->reservations_count }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="2" class="px-4 py-3 text-center text-gray-500">No data available</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-bold mb-4">Guest Statistics</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center p-4 bg-gray-50 rounded">
            <p class="text-2xl font-bold">{{ $guestStats['total'] }}</p>
            <p class="text-sm text-gray-500">Total Guests</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded">
            <p class="text-2xl font-bold">{{ $guestStats['newThisMonth'] }}</p>
            <p class="text-sm text-gray-500">New This Month</p>
        </div>
        @if($isHotelMode)
        <div class="text-center p-4 bg-gray-50 rounded">
            <p class="text-2xl font-bold">{{ $guestStats['withLoyalty'] }}</p>
            <p class="text-sm text-gray-500">Loyalty Members</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded">
            <p class="text-2xl font-bold">{{ $guestStats['avgNights'] }}</p>
            <p class="text-sm text-gray-500">Avg Nights</p>
        </div>
        @endif
    </div>
</div>

<div class="mt-8 flex gap-4">
    <a href="{{ route('admin.reports.revenue') }}" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90">
        <i class="fas fa-chart-line mr-2"></i>Detailed Revenue
    </a>
    <a href="{{ route('admin.reports.occupancy') }}" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90">
        <i class="fas fa-bed mr-2"></i>Occupancy Report
    </a>
    <a href="{{ route('admin.reports.guests') }}" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90">
        <i class="fas fa-users mr-2"></i>Guest Report
    </a>
</div>
@endsection