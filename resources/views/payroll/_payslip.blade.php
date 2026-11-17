{{-- Payslip breakdown; shared by the staff portal page. --}}
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold mb-3">Earnings</h3>
        <dl class="text-sm divide-y">
            @foreach([['Basic', $payslip->basic], ['Housing', $payslip->housing], ['Transport', $payslip->transport], ['Other allowance', $payslip->other_allowance]] as [$label, $value])
            @if((float) $value > 0)<div class="py-2 flex justify-between"><dt>{{ $label }}</dt><dd>{{ $m($value) }}</dd></div>@endif
            @endforeach
            @if((float) $payslip->overtime_pay > 0)<div class="py-2 flex justify-between"><dt>Overtime ({{ $payslip->overtime_hours }} h)</dt><dd>{{ $m($payslip->overtime_pay) }}</dd></div>@endif
            @if($payslip->absent_days)<div class="py-2 flex justify-between text-orange-700"><dt>Absence ({{ $payslip->absent_days }} {{ Str::plural('day', $payslip->absent_days) }})</dt><dd>−{{ $m($payslip->absence_deduction) }}</dd></div>@endif
            <div class="py-2 flex justify-between font-semibold"><dt>Gross pay</dt><dd>{{ $m($payslip->gross) }}</dd></div>
        </dl>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold mb-3">Deductions</h3>
        <dl class="text-sm divide-y">
            <div class="py-2 flex justify-between"><dt>Pension (employee)</dt><dd>{{ $m($payslip->pension_employee) }}</dd></div>
            @if((float) $payslip->nhf > 0)<div class="py-2 flex justify-between"><dt>NHF</dt><dd>{{ $m($payslip->nhf) }}</dd></div>@endif
            <div class="py-2 flex justify-between"><dt>PAYE tax</dt><dd>{{ $m($payslip->paye) }}</dd></div>
            @foreach($payslip->details['deductions'] ?? [] as $line)
            <div class="py-2 flex justify-between"><dt>{{ $line['description'] }}</dt><dd>{{ $m($line['amount']) }}</dd></div>
            @endforeach
            <div class="py-2 flex justify-between font-semibold"><dt>Total deductions</dt><dd>{{ $m($payslip->totalDeductions()) }}</dd></div>
        </dl>
    </div>
</div>
<div class="mt-6 bg-primary text-white rounded-lg p-5 flex justify-between items-center">
    <span class="text-lg">Net pay</span>
    <span class="text-2xl font-bold">{{ $m($payslip->net) }}</span>
</div>
<p class="mt-3 text-xs text-gray-500">Your employer also paid {{ $m($payslip->pension_employer) }} into your pension. {{ $payslip->details['shifts_worked'] ?? 0 }} shifts, {{ $payslip->details['hours_worked'] ?? 0 }} hours recorded this month.</p>
