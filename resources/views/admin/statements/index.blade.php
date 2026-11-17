@extends('layouts.admin')
@section('page_title', 'Owner Statements')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Owner Statements · {{ $month->format('F Y') }}</h2>
        <p class="text-sm text-gray-500">Paid stays that checked out in the month, less commission and owner expenses. Approve a statement to show it in the owner's portal.</p>
    </div>
    <div class="flex flex-wrap gap-2 items-end">
        <form method="GET" class="flex gap-2">
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}" class="border rounded-lg px-3 py-2 text-sm">
            <button type="submit" class="px-4 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary">Show</button>
        </form>
        @if($ownersWithout->isNotEmpty() || $statements->where('status', 'draft')->isNotEmpty())
        <form method="POST" action="{{ route('admin.statements.generate') }}">
            @csrf
            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-primary/90"><i class="fas fa-calculator mr-1"></i> Prepare all drafts</button>
        </form>
        @endif
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden table-responsive mb-6">
    <table class="min-w-full mobile-card-table text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Commission</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Expenses</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Payout</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($statements as $statement)
            <tr>
                <td class="px-4 py-3" data-label="Owner"><a href="{{ route('admin.statements.show', $statement) }}" class="text-primary hover:underline font-medium">{{ $statement->owner->name }}</a></td>
                <td class="px-4 py-3 text-right" data-label="Revenue">₦{{ number_format($statement->gross_revenue, 2) }}</td>
                <td class="px-4 py-3 text-right" data-label="Commission">₦{{ number_format($statement->commission_amount, 2) }} <span class="text-xs text-gray-400">({{ rtrim(rtrim($statement->commission_rate, '0'), '.') }}%)</span></td>
                <td class="px-4 py-3 text-right" data-label="Expenses">₦{{ number_format($statement->expenses_total, 2) }}</td>
                <td class="px-4 py-3 text-right font-semibold {{ $statement->net_payout < 0 ? 'text-red-600' : '' }}" data-label="Payout">₦{{ number_format($statement->net_payout, 2) }}</td>
                <td class="px-4 py-3" data-label="Status">
                    @php $badge = ['draft' => 'bg-yellow-100 text-yellow-800', 'approved' => 'bg-blue-100 text-blue-800', 'paid' => 'bg-green-100 text-green-800'][$statement->status]; @endphp
                    <span class="text-xs px-2 py-1 rounded-full {{ $badge }}">{{ \App\Models\OwnerStatement::STATUSES[$statement->status] }}</span>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No statements for {{ $month->format('F Y') }} yet. Use "Prepare all drafts".</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($ownersWithout->isNotEmpty())
<p class="text-sm text-gray-500">Not yet prepared: {{ $ownersWithout->pluck('name')->join(', ') }}.</p>
@endif
@endsection
