<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; color: #0B2D5B; margin: 0; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 5px 8px; }
        .box td { border-bottom: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .head { background: #f3f4f6; font-weight: bold; }
        .net td { background: #0B2D5B; color: #fff; font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>
    @php $m = fn ($v) => 'NGN ' . number_format((float) $v, 2); $s = $payslip->staff; @endphp
    <table>
        <tr>
            <td><h1>{{ config('app.name', 'BookTala') }}</h1><div class="muted">Payslip · {{ $payslip->payRun->periodLabel() }}</div></td>
            <td class="right">
                <strong>{{ $s->user->name ?? '' }}</strong><br>
                Employee ID {{ $s->employee_id }}<br>
                @if($s->tax_id)TIN {{ $s->tax_id }}<br>@endif
                @if($s->pension_pin)Pension PIN {{ $s->pension_pin }}@endif
            </td>
        </tr>
    </table>
    <br>
    <table class="box">
        <tr class="head"><td>Earnings</td><td class="right">Amount</td></tr>
        <tr><td>Basic</td><td class="right">{{ $m($payslip->basic) }}</td></tr>
        @if((float) $payslip->housing)<tr><td>Housing</td><td class="right">{{ $m($payslip->housing) }}</td></tr>@endif
        @if((float) $payslip->transport)<tr><td>Transport</td><td class="right">{{ $m($payslip->transport) }}</td></tr>@endif
        @if((float) $payslip->other_allowance)<tr><td>Other allowance</td><td class="right">{{ $m($payslip->other_allowance) }}</td></tr>@endif
        @if((float) $payslip->overtime_pay)<tr><td>Overtime ({{ $payslip->overtime_hours }} h)</td><td class="right">{{ $m($payslip->overtime_pay) }}</td></tr>@endif
        @if($payslip->absent_days)<tr><td>Absence ({{ $payslip->absent_days }} days)</td><td class="right">-{{ $m($payslip->absence_deduction) }}</td></tr>@endif
        <tr class="head"><td>Gross pay</td><td class="right">{{ $m($payslip->gross) }}</td></tr>
    </table>
    <br>
    <table class="box">
        <tr class="head"><td>Deductions</td><td class="right">Amount</td></tr>
        <tr><td>Pension (employee)</td><td class="right">{{ $m($payslip->pension_employee) }}</td></tr>
        @if((float) $payslip->nhf)<tr><td>NHF</td><td class="right">{{ $m($payslip->nhf) }}</td></tr>@endif
        <tr><td>PAYE tax</td><td class="right">{{ $m($payslip->paye) }}</td></tr>
        @foreach($payslip->details['deductions'] ?? [] as $line)
        <tr><td>{{ $line['description'] }}</td><td class="right">{{ $m($line['amount']) }}</td></tr>
        @endforeach
        <tr class="head"><td>Total deductions</td><td class="right">{{ $m($payslip->totalDeductions()) }}</td></tr>
    </table>
    <br>
    <table><tr class="net"><td>Net pay</td><td class="right">{{ $m($payslip->net) }}</td></tr></table>
    <p class="muted">Employer pension contribution: {{ $m($payslip->pension_employer) }}.
        @if($s->bank_account_number) Paid to {{ $s->bank_name }} ···{{ substr($s->bank_account_number, -4) }}.@endif
        {{ $payslip->details['shifts_worked'] ?? 0 }} shifts, {{ $payslip->details['hours_worked'] ?? 0 }} hours recorded.</p>
</body>
</html>
