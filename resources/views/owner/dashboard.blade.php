@extends('layouts.owner')
@section('page_title', 'Overview')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Welcome, {{ $owner->name }}</h2>
        <p class="text-sm text-gray-500">Your {{ Str::plural('property', $properties->count()) }} managed by G-Briel Property Ltd.</p>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('owner.dashboard') }}" class="flex flex-wrap items-center gap-2 bg-white p-2 rounded-lg shadow-sm border border-gray-100">
        <div class="flex items-center gap-1.5">
            <label for="month" class="text-xs font-semibold text-gray-500 uppercase">Period:</label>
            <input type="month" id="month" name="month" value="{{ $finance['period'] }}" class="text-xs rounded border-gray-300 focus:ring-accent focus:border-accent py-1 px-2">
        </div>
        <div class="flex items-center gap-1.5">
            <label for="property_id" class="text-xs font-semibold text-gray-500 uppercase">Property:</label>
            <select id="property_id" name="property_id" class="text-xs rounded border-gray-300 focus:ring-accent focus:border-accent py-1 px-2">
                <option value="">All Properties</option>
                @foreach($finance['available_properties'] as $p)
                    <option value="{{ $p->id }}" {{ $selectedPropertyId == $p->id ? 'selected' : '' }}>{{ $p->title }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-3 py-1 bg-accent text-white text-xs font-semibold rounded hover:bg-accent/90 transition">
            Filter
        </button>
        @if(request()->has('month') || request()->has('property_id'))
            <a href="{{ route('owner.dashboard') }}" class="text-xs text-gray-400 hover:text-gray-600 px-1">Reset</a>
        @endif
    </form>
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

<!-- Financial Overview KPI Cards -->
<h3 class="text-lg font-semibold mb-3">Financial Summary · {{ $finance['period_label'] }}</h3>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-blue-500">
        <div class="text-xs text-gray-500 uppercase font-semibold">Earned Revenue</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">₦{{ number_format($finance['totals']['total_earned_revenue'], 2) }}</div>
        <div class="text-xs text-gray-500 mt-2">Paid completed stays recognized</div>
    </div>

    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-amber-500">
        <div class="text-xs text-gray-500 uppercase font-semibold">Deductions</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">₦{{ number_format($finance['totals']['total_commission'] + $finance['totals']['total_expenses'], 2) }}</div>
        <div class="text-xs text-gray-500 mt-2">
            Comm: ₦{{ number_format($finance['totals']['total_commission'], 2) }} · Exp: ₦{{ number_format($finance['totals']['total_expenses'], 2) }}
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-emerald-500">
        <div class="text-xs text-gray-500 uppercase font-semibold">Estimated Net Entitlement</div>
        <div class="text-2xl font-bold text-emerald-600 mt-1">₦{{ number_format($finance['totals']['total_net_entitlement'], 2) }}</div>
        <div class="text-xs text-gray-500 mt-2">Pre-payout operational balance</div>
    </div>

    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-purple-500">
        <div class="text-xs text-gray-500 uppercase font-semibold">Caution Held (Liability)</div>
        <div class="text-2xl font-bold text-purple-600 mt-1">₦{{ number_format($finance['totals']['total_caution_held'], 2) }}</div>
        <div class="text-xs text-gray-500 mt-2">Active security deposits held</div>
    </div>
</div>

<!-- Property Financial Breakdown Table -->
<h3 class="text-lg font-semibold mb-3">Property Financial Breakdown · {{ $finance['period_label'] }}</h3>
<div class="bg-white rounded-lg shadow overflow-hidden table-responsive mb-8">
    <table class="min-w-full mobile-card-table text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nights</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Earned Rev</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Commission</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Expenses</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase font-bold">Net Entitlement</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($finance['properties'] as $row)
            <tr>
                <td class="px-4 py-3 font-semibold text-gray-900" data-label="Property">
                    {{ $row['title'] }}
                    @if(!empty($row['unit_code']))
                        <span class="text-xs text-gray-400 font-normal">({{ $row['unit_code'] }})</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-500" data-label="Site">{{ $row['site_name'] }}</td>
                <td class="px-4 py-3 text-right" data-label="Nights">{{ $row['nights_booked'] }}</td>
                <td class="px-4 py-3 text-right font-medium" data-label="Earned Rev">₦{{ number_format($row['earned_revenue'], 2) }}</td>
                <td class="px-4 py-3 text-right text-amber-700" data-label="Commission">-₦{{ number_format($row['commission_deductions'], 2) }}</td>
                <td class="px-4 py-3 text-right text-red-700" data-label="Expenses">-₦{{ number_format($row['recoverable_expenses'], 2) }}</td>
                <td class="px-4 py-3 text-right font-bold text-emerald-600" data-label="Net Entitlement">₦{{ number_format($row['net_entitlement'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-6 text-center text-gray-500">No property records found for this period.</td>
            </tr>
            @endforelse
        </tbody>
        @if(!empty($finance['properties']))
        <tfoot class="bg-gray-50 font-bold border-t border-gray-200">
            <tr>
                <td class="px-4 py-3" colspan="2">Total</td>
                <td class="px-4 py-3 text-right">{{ collect($finance['properties'])->sum('nights_booked') }}</td>
                <td class="px-4 py-3 text-right">₦{{ number_format($finance['totals']['total_earned_revenue'], 2) }}</td>
                <td class="px-4 py-3 text-right text-amber-700">-₦{{ number_format($finance['totals']['total_commission'], 2) }}</td>
                <td class="px-4 py-3 text-right text-red-700">-₦{{ number_format($finance['totals']['total_expenses'], 2) }}</td>
                <td class="px-4 py-3 text-right text-emerald-600">₦{{ number_format($finance['totals']['total_net_entitlement'], 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<!-- Occupancy -->
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

<!-- Upcoming Stays -->
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
