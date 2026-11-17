<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement {{ $statement->owner->name }} {{ $statement->periodLabel() }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; color: #0B2D5B; margin: 0; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; color: #6b7280; }
        .right { text-align: right; }
        .summary td { border: none; padding: 3px 8px; }
        .total td { font-weight: bold; border-top: 2px solid #0B2D5B; font-size: 13px; }
    </style>
</head>
<body>
    @php $owner = $statement->owner; $naira = fn ($v) => ($v < 0 ? '-' : '') . 'NGN ' . number_format(abs($v), 2); @endphp
    <table class="summary" style="margin-top:0">
        <tr>
            <td><h1>{{ config('app.name', 'BookTala') }}</h1><div class="muted">Owner statement</div></td>
            <td class="right">
                <strong>{{ $owner->name }}</strong><br>
                {{ $statement->periodLabel() }}<br>
                <span class="muted">{{ \App\Models\OwnerStatement::STATUSES[$statement->status] }}@if($statement->paid_at) · paid {{ $statement->paid_at->format('j M Y') }}@endif</span>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr><td>Room revenue ({{ $statement->nights_booked }} nights)</td><td class="right">{{ $naira($statement->gross_revenue) }}</td></tr>
        <tr><td>Management commission ({{ rtrim(rtrim($statement->commission_rate, '0'), '.') }}%)</td><td class="right">{{ $naira(-1 * $statement->commission_amount) }}</td></tr>
        <tr><td>Expenses</td><td class="right">{{ $naira(-1 * $statement->expenses_total) }}</td></tr>
        <tr class="total"><td>{{ $owner->is_company_owned ? 'Net income' : 'Payout' }}</td><td class="right">{{ $naira($statement->net_payout) }}</td></tr>
        @if($owner->bank_account_number && !$owner->is_company_owned)
        <tr><td colspan="2" class="muted">Paid to {{ $owner->bank_account_name }} · {{ $owner->bank_name }} · ···{{ substr($owner->bank_account_number, -4) }}@if($statement->payment_reference) · ref {{ $statement->payment_reference }}@endif</td></tr>
        @endif
    </table>

    <table>
        <thead><tr><th>Date</th><th>Property</th><th>Item</th><th class="right">Amount</th></tr></thead>
        <tbody>
            @forelse($statement->lines as $line)
            <tr>
                <td>{{ $line->date->format('j M Y') }}</td>
                <td>{{ $line->property->title ?? '-' }}</td>
                <td>{{ $line->description }}@if($line->nights) ({{ $line->nights }} nights)@endif</td>
                <td class="right">{{ $naira($line->amount) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="muted">No completed paid stays or expenses in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="muted" style="margin-top:14px">Revenue is the room price after discounts for paid stays that checked out in the period; VAT is excluded. Generated {{ now()->format('j M Y, g:i A') }}.</p>
</body>
</html>
