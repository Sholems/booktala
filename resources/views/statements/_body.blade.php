{{-- Statement summary and lines; shared by the admin page and the owner portal. --}}
@php $owner = $statement->owner; @endphp
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    @foreach([
        ['Room revenue', $statement->gross_revenue, $statement->nights_booked . ' nights booked'],
        ['Management commission', -1 * $statement->commission_amount, rtrim(rtrim($statement->commission_rate, '0'), '.') . '% of revenue'],
        ['Expenses', -1 * $statement->expenses_total, $owner->deduct_expenses ? 'Charged to owner' : 'Not deducted'],
        [$owner->is_company_owned ? 'Net income' : 'Payout to owner', $statement->net_payout, $owner->bank_account_number ? ($owner->bank_name . ' ···' . substr($owner->bank_account_number, -4)) : ''],
    ] as [$label, $amount, $note])
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-500">{{ $label }}</div>
        <div class="text-xl font-bold {{ $amount < 0 ? 'text-gray-700' : '' }}">{{ $amount < 0 ? '−' : '' }}₦{{ number_format(abs($amount), 2) }}</div>
        @if($note)<div class="text-xs text-gray-400">{{ $note }}</div>@endif
    </div>
    @endforeach
</div>

<div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
    <table class="min-w-full mobile-card-table text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($statement->lines as $line)
            <tr>
                <td class="px-4 py-3 whitespace-nowrap" data-label="Date">{{ $line->date->format('j M Y') }}</td>
                <td class="px-4 py-3" data-label="Property">{{ $line->property->title ?? '—' }}</td>
                <td class="px-4 py-3" data-label="Item">{{ $line->description }}@if($line->nights) <span class="text-gray-400">· {{ $line->nights }} {{ Str::plural('night', $line->nights) }}</span>@endif</td>
                <td class="px-4 py-3 text-right whitespace-nowrap {{ $line->amount < 0 ? 'text-red-600' : '' }}" data-label="Amount">{{ $line->amount < 0 ? '−' : '' }}₦{{ number_format(abs($line->amount), 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No completed paid stays or expenses in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<p class="mt-3 text-xs text-gray-500">Revenue is the room price after discounts for paid stays that checked out in {{ $statement->periodLabel() }}; VAT is excluded. Expenses include any owner expense recorded up to the month end that was not on an earlier statement.</p>
