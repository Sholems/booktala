@extends('layouts.admin')

@section('page_title', 'Dashboard')

@section('content')
{{-- Quick Actions --}}
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 md:gap-4 mb-6">
    <a href="{{ route('admin.properties.create') }}" class="bg-primary text-white p-4 rounded-lg text-center hover:bg-primary/90 transition">
        <i class="fas fa-plus-circle text-xl mb-1"></i>
        <p class="text-xs font-medium">Add Property</p>
    </a>
    <a href="{{ route('admin.reservations.index') }}" class="bg-blue-600 text-white p-4 rounded-lg text-center hover:bg-blue-600/90 transition">
        <i class="fas fa-calendar-check text-xl mb-1"></i>
        <p class="text-xs font-medium">New Booking</p>
    </a>
    <a href="{{ route('admin.invoices.create') }}" class="bg-green-600 text-white p-4 rounded-lg text-center hover:bg-green-600/90 transition">
        <i class="fas fa-file-invoice text-xl mb-1"></i>
        <p class="text-xs font-medium">Create Invoice</p>
    </a>
    <a href="{{ route('admin.tasks.index') }}" class="bg-yellow-600 text-white p-4 rounded-lg text-center hover:bg-yellow-600/90 transition">
        <i class="fas fa-tasks text-xl mb-1"></i>
        <p class="text-xs font-medium">Assign Task</p>
    </a>
    <a href="{{ route('admin.messages.index') }}" class="bg-purple-600 text-white p-4 rounded-lg text-center hover:bg-purple-600/90 transition">
        <i class="fas fa-envelope text-xl mb-1"></i>
        <p class="text-xs font-medium">Messages</p>
    </a>
    <a href="{{ route('admin.reports.index') }}" class="bg-orange-600 text-white p-4 rounded-lg text-center hover:bg-orange-600/90 transition">
        <i class="fas fa-chart-bar text-xl mb-1"></i>
        <p class="text-xs font-medium">Reports</p>
    </a>
</div>

{{-- Needs attention: each tile opens the list of those items --}}
@php
    $toneClasses = [
        'blue' => 'bg-blue-50 text-blue-700', 'indigo' => 'bg-indigo-50 text-indigo-700',
        'amber' => 'bg-amber-50 text-amber-700', 'red' => 'bg-red-50 text-red-700',
        'purple' => 'bg-purple-50 text-purple-700', 'orange' => 'bg-orange-50 text-orange-700',
        'teal' => 'bg-teal-50 text-teal-700', 'green' => 'bg-green-50 text-green-700',
    ];
    $openItems = collect($queues)->sum('count');
@endphp
<div class="mb-8">
    <div class="flex items-baseline justify-between mb-3">
        <h2 class="text-lg font-bold">Needs attention</h2>
        <span class="text-sm text-gray-500">{{ $openItems === 0 ? 'All clear' : $openItems . ' open ' . \Illuminate\Support\Str::plural('item', $openItems) }}</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
        @foreach($queues as $queue)
        <a href="{{ $queue['url'] }}" class="group flex items-center gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-100 hover:ring-primary/30 hover:shadow transition {{ $queue['count'] === 0 ? 'opacity-60' : '' }}">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $queue['count'] > 0 ? $toneClasses[$queue['tone']] : 'bg-gray-50 text-gray-400' }}">
                <i class="fas {{ $queue['icon'] }}"></i>
            </span>
            <span class="min-w-0">
                <span class="block text-xl font-bold leading-none {{ $queue['count'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">{{ $queue['count'] }}</span>
                <span class="mt-1 block text-xs text-gray-600 leading-tight">{{ $queue['label'] }}</span>
            </span>
        </a>
        @endforeach
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
    <div class="bg-white p-4 md:p-6 rounded-lg shadow border-l-4 border-primary">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Properties</p>
                <p class="text-xl md:text-2xl font-bold">{{ $stats['total_properties'] ?? 0 }}</p>
                <p class="text-xs text-green-500">{{ $stats['active_properties'] ?? 0 }} active</p>
            </div>
            <i class="fas fa-building text-2xl md:text-3xl text-gray-300"></i>
        </div>
    </div>
    
    <div class="bg-white p-4 md:p-6 rounded-lg shadow border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Bookings</p>
                <p class="text-xl md:text-2xl font-bold">{{ $stats['total_bookings'] ?? 0 }}</p>
                @if(isset($stats['bookings_growth']))
                <p class="text-xs {{ $stats['bookings_growth'] >= 0 ? 'text-green-500' : 'text-red-500' }}">
                    {{ $stats['bookings_growth'] >= 0 ? '+' : '' }}{{ $stats['bookings_growth'] }}% vs last month
                </p>
                @endif
            </div>
            <i class="fas fa-calendar-alt text-2xl md:text-3xl text-gray-300"></i>
        </div>
    </div>
    
    <div class="bg-white p-4 md:p-6 rounded-lg shadow border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Occupancy Rate</p>
                <p class="text-xl md:text-2xl font-bold">{{ $stats['occupancy_rate'] ?? 0 }}%</p>
                <p class="text-xs text-gray-500">{{ $stats['confirmed_bookings'] ?? 0 }} confirmed</p>
            </div>
            <i class="fas fa-bed text-2xl md:text-3xl text-gray-300"></i>
        </div>
    </div>
    
    <div class="bg-white p-4 md:p-6 rounded-lg shadow border-l-4 border-accent">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Revenue</p>
                <p class="text-xl md:text-2xl font-bold">₦{{ number_format($stats['total_revenue'] ?? 0) }}</p>
                @if(isset($stats['revenue_growth']))
                <p class="text-xs {{ $stats['revenue_growth'] >= 0 ? 'text-green-500' : 'text-red-500' }}">
                    {{ $stats['revenue_growth'] >= 0 ? '+' : '' }}{{ $stats['revenue_growth'] }}% vs last month
                </p>
                @endif
            </div>
            <i class="fas fa-money-bill text-2xl md:text-3xl text-gray-300"></i>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">This Month Revenue</p>
            <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Current</span>
        </div>
        <p class="text-2xl font-bold text-primary">₦{{ number_format($stats['this_month_revenue'] ?? 0) }}</p>
        <p class="text-xs text-gray-500">Last month: ₦{{ number_format($stats['last_month_revenue'] ?? 0) }}</p>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Today Check-ins</p>
            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Active</span>
        </div>
        <p class="text-2xl font-bold text-blue-600">{{ $stats['today_checkins'] ?? 0 }}</p>
        <p class="text-xs text-gray-500">{{ $stats['today_checkouts'] ?? 0 }} checkouts today</p>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Pending Tasks</p>
            <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Todo</span>
        </div>
        <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending_tasks'] ?? 0 }}</p>
        <p class="text-xs text-gray-500">Tasks awaiting action</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-bold mb-4">Monthly Revenue</h3>
        <canvas id="revenueChart" height="200"></canvas>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-bold mb-4">Top Performing Properties</h3>
        @forelse($topProperties ?? [] as $property)
        <div class="flex items-center justify-between py-3 border-b">
            <div class="flex items-center">
                @if($property->primaryImage)
                <img src="{{ $property->primaryImage->image_path }}" class="w-10 h-10 rounded object-cover mr-3">
                @else
                <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center mr-3">
                    <i class="fas fa-building text-gray-400"></i>
                </div>
                @endif
                <div>
                    <p class="font-medium text-sm">{{ $property->title }}</p>
                    <p class="text-xs text-gray-500">{{ $property->reservations_count }} bookings</p>
                </div>
            </div>
            <span class="text-sm font-bold text-primary">₦{{ number_format($property->price_per_night) }}/night</span>
        </div>
        @empty
        <p class="text-gray-500">No property data available</p>
        @endforelse
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-bold mb-4">Recent activity</h3>
        <ol class="space-y-3">
            @forelse($activity as $event)
            <li class="flex gap-3 text-sm">
                <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500"><i class="fas {{ $event['icon'] }} text-xs"></i></span>
                <span class="min-w-0">
                    @if($event['url'])
                    <a href="{{ $event['url'] }}" class="text-gray-800 hover:text-primary hover:underline">{{ $event['text'] }}</a>
                    @else
                    <span class="text-gray-800">{{ $event['text'] }}</span>
                    @endif
                    <span class="block text-xs text-gray-500" title="{{ $event['at']->format('M d, Y g:i A') }}">{{ $event['at']->diffForHumans() }}</span>
                </span>
            </li>
            @empty
            <li class="text-gray-500 text-sm">No activity in the last two weeks.</li>
            @endforelse
        </ol>
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-bold mb-4">Upcoming Check-ins</h3>
        @forelse($upcomingCheckins ?? [] as $checkin)
        <div class="border-b py-3">
            <p class="font-medium">{{ $checkin->user->name ?? 'N/A' }}</p>
            <p class="text-sm text-gray-500">{{ $checkin->property->title ?? 'N/A' }} - {{ \Carbon\Carbon::parse($checkin->check_in)->format('M d, Y') }}</p>
        </div>
        @empty
        <p class="text-gray-500">No upcoming check-ins</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
@vite('resources/js/charts.js')
<script>
document.addEventListener('DOMContentLoaded', function () {
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Revenue (₦)',
            data: @json(array_values($stats['monthly_revenue'] ?? array_fill(1, 12, 0))),
            backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#0B2D5B',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: { 
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₦' + (value / 1000000).toFixed(1) + 'M';
                    }
                }
            }
        }
    }
});
});
</script>
@endpush
