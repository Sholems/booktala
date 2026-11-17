@extends('layouts.owner')
@section('page_title', 'Overview')
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold">Welcome, {{ $owner->name }}</h2>
    <p class="text-sm text-gray-500">Your {{ Str::plural('property', $properties->count()) }} managed by G-Briel Property Ltd.</p>
</div>

@if($latest)
<a href="{{ route('owner.statements.show', $latest) }}" class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2 rounded-lg bg-primary text-white p-5 hover:bg-primary/90">
    <div>
        <div class="text-sm opacity-80">Latest statement · {{ $latest->periodLabel() }}</div>
        <div class="text-2xl font-bold">₦{{ number_format($latest->net_payout, 2) }}</div>
    </div>
    <span class="text-sm">{{ $latest->status === 'paid' ? 'Paid ' . $latest->paid_at->format('j M Y') : 'Payout pending' }} <i class="fas fa-chevron-right ml-1"></i></span>
</a>
@endif

<h3 class="text-lg font-semibold mb-3">Occupancy · {{ $monthStart->format('F Y') }}</h3>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    @forelse($properties as $property)
    @php $booked = (int) ($nights[$property->id] ?? 0); $pct = $daysInMonth ? round($booked / $daysInMonth * 100) : 0; @endphp
    <div class="bg-white rounded-lg shadow p-5">
        <div class="font-semibold">{{ $property->title }}</div>
        <div class="text-xs text-gray-500 mb-3">{{ $property->site->name ?? $property->city }} · {{ ucfirst($property->status) }}</div>
        <div class="flex items-end justify-between mb-1">
            <span class="text-2xl font-bold">{{ $pct }}%</span>
            <span class="text-xs text-gray-500">{{ $booked }} of {{ $daysInMonth }} nights booked</span>
        </div>
        <div class="h-2 rounded-full bg-gray-100 overflow-hidden" role="img" aria-label="{{ $pct }}% occupied">
            <div class="h-2 bg-primary" style="width: {{ min(100, $pct) }}%"></div>
        </div>
    </div>
    @empty
    <p class="text-gray-500">No properties are linked to your account yet.</p>
    @endforelse
</div>

<h3 class="text-lg font-semibold mb-3">Current and upcoming stays</h3>
<div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
    <table class="min-w-full mobile-card-table text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nights</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($upcoming as $stay)
            <tr>
                <td class="px-4 py-3" data-label="Property">{{ $stay->property->title }}</td>
                <td class="px-4 py-3 whitespace-nowrap" data-label="Dates">{{ $stay->check_in->format('j M') }} – {{ $stay->check_out->format('j M Y') }}</td>
                <td class="px-4 py-3" data-label="Nights">{{ $stay->nights }}</td>
                <td class="px-4 py-3" data-label="Status">{{ $stay->booking_status === 'checked_in' ? 'In residence' : 'Confirmed' }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No current or upcoming stays.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<p class="mt-3 text-xs text-gray-500">Guest details are kept private. Revenue appears on your monthly statement once each stay is completed and paid.</p>
@endsection
