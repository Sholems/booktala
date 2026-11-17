@extends('layouts.admin')
@section('page_title', 'Revenue Report')
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

<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h3 class="text-lg font-bold mb-4">Revenue by Month</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Month</th>
                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse($revenueByMonth as $item)
                <tr class="border-t">
                    <td class="px-4 py-3">{{ \Carbon\Carbon::create($item->year, $item->month)->format('F Y') }}</td>
                    <td class="px-4 py-3 text-right font-medium">₦{{ number_format($item->total) }}</td>
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

<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-bold mb-4">Revenue by Property</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Property</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Bookings</th>
                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse($revenueByProperty as $item)
                <tr class="border-t">
                    <td class="px-4 py-3">{{ $item['property'] }}</td>
                    <td class="px-4 py-3 text-center">{{ $item['count'] }}</td>
                    <td class="px-4 py-3 text-right font-medium">₦{{ number_format($item['total']) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-4 py-3 text-center text-gray-500">No data available</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection