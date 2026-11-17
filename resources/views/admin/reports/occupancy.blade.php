@extends('layouts.admin')
@php
$isHotelMode = \App\Models\SystemSetting::get('hotel_mode', false);
@endphp
@section('page_title', 'Occupancy Report')
@section('content')
<div class="mb-6">
    <form method="GET" class="flex gap-4 items-end">
        <div>
            <label class="block text-sm font-medium mb-1">Year</label>
            <select name="year" class="border rounded px-3 py-2">
                @for($y = now()->year; $y >= now()->year - 5; $y--)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <button type="submit" class="bg-primary text-white px-4 py-2 rounded">Filter</button>
    </form>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h3 class="text-lg font-bold mb-4">Monthly Occupancy Rate - {{ $year }}</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Month</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Occupancy Rate</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Bar</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyOccupancy as $item)
                @php $rateVal = $item['rate']; @endphp
                <tr class="border-t">
                    <td class="px-4 py-3">{{ $item['month'] }}</td>
                    <td class="px-4 py-3 text-center">{{ $rateVal }}%</td>
                    <td class="px-4 py-3">
                        <div class="h-4 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-primary rounded-full" data-width="{{ $rateVal }}"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
document.querySelectorAll('[data-width]').forEach(function(el) {
    el.style.width = el.getAttribute('data-width') + '%';
});
</script>

@if($isHotelMode && $roomTypeStats)
<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-bold mb-4">Room Type Performance</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($roomTypeStats as $type)
        @php $typeRate = $type['rate']; @endphp
        <div class="border rounded p-4">
            <div class="flex justify-between items-center mb-2">
                <span class="font-medium">{{ $type['name'] }}</span>
                <span class="text-sm font-bold text-primary">{{ $typeRate }}%</span>
            </div>
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden mb-2">
                <div class="h-full bg-accent rounded-full" data-width="{{ $typeRate }}"></div>
            </div>
            <div class="text-sm text-gray-500">
                {{ $type['occupied'] }} occupied / {{ $type['available'] }} available
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection