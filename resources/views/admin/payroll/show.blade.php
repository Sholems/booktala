@extends('layouts.admin')
@section('page_title', 'Pay Run')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <a href="{{ route('admin.payroll.index') }}" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-arrow-left mr-1"></i> Payroll</a>
        <h2 class="mt-1 text-2xl font-bold">{{ $run->periodLabel() }} pay run</h2>
        <p class="text-sm text-gray-500">
            {{ \App\Models\PayRun::STATUSES[$run->status] }} · {{ $run->payslips->count() }} payslips
            @if($run->approved_at) · approved {{ $run->approved_at->format('j M Y') }} by {{ $run->approvedBy->name ?? '' }}@endif
            @if($run->paid_at) · paid {{ $run->paid_at->format('j M Y') }}@endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($run->isDraft())
        <form method="POST" action="{{ route('admin.payroll.generate') }}">
            @csrf
            <input type="hidden" name="month" value="{{ $run->period_start->format('Y-m') }}">
            <button type="submit" class="px-4 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary"><i class="fas fa-sync mr-1"></i> Recalculate</button>
        </form>
        <form method="POST" action="{{ route('admin.payroll.approve', $run) }}" onsubmit="return confirm('Approve this pay run? Payslips will be frozen and visible to staff.')">
            @csrf @method('PATCH')
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-primary/90">Approve</button>
        </form>
        @else
        <a href="{{ route('admin.payroll.bank-schedule', $run) }}" class="px-4 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary"><i class="fas fa-university mr-1"></i> Bank schedule (CSV)</a>
        @if($run->status === 'approved')
        <form method="POST" action="{{ route('admin.payroll.paid', $run) }}" onsubmit="return confirm('Mark salaries as paid? Loan balances will be reduced.')">
            @csrf @method('PATCH')
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">Mark as paid</button>
        </form>
        @endif
        @endif
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    @foreach([['Gross pay', $run->total_gross], ['Deductions', $run->total_deductions], ['Net pay (to banks)', $run->total_net], ['Employer pension', $run->total_employer_pension]] as [$label, $value])
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-500">{{ $label }}</div>
        <div class="text-xl font-bold">₦{{ number_format($value, 2) }}</div>
    </div>
    @endforeach
</div>

<div class="bg-white rounded-lg shadow overflow-hidden table-responsive mb-6">
    <table class="min-w-full mobile-card-table text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Salary</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Overtime</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Absence</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gross</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pension</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">NHF</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">PAYE</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Loans etc.</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net</th>
                <th class="px-3 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($run->payslips->sortBy('staff.user.name') as $slip)
            <tr>
                <td class="px-3 py-3" data-label="Staff">
                    <div class="font-medium">{{ $slip->staff->user->name ?? $slip->staff->employee_id }}</div>
                    <div class="text-xs text-gray-500">{{ $slip->details['shifts_worked'] ?? 0 }} shifts · {{ $slip->details['hours_worked'] ?? 0 }} h
                        @if(!$slip->staff->bank_account_number)<span class="text-orange-600">· no bank account</span>@endif</div>
                </td>
                <td class="px-3 py-3 text-right" data-label="Salary">{{ number_format($slip->basic + $slip->housing + $slip->transport + $slip->other_allowance, 2) }}</td>
                <td class="px-3 py-3 text-right" data-label="Overtime">@if($slip->overtime_pay > 0){{ number_format($slip->overtime_pay, 2) }}@elseif($slip->overtime_hours > 0)<span class="text-gray-400" title="No overtime rate set">{{ $slip->overtime_hours }} h</span>@else — @endif</td>
                <td class="px-3 py-3 text-right" data-label="Absence">@if($slip->absent_days)<span class="text-orange-600">−{{ number_format($slip->absence_deduction, 2) }}</span> <span class="text-xs text-gray-400">({{ $slip->absent_days }}d)</span>@else — @endif</td>
                <td class="px-3 py-3 text-right font-medium" data-label="Gross">{{ number_format($slip->gross, 2) }}</td>
                <td class="px-3 py-3 text-right" data-label="Pension">{{ number_format($slip->pension_employee, 2) }}</td>
                <td class="px-3 py-3 text-right" data-label="NHF">{{ number_format($slip->nhf, 2) }}</td>
                <td class="px-3 py-3 text-right" data-label="PAYE">{{ number_format($slip->paye, 2) }}</td>
                <td class="px-3 py-3 text-right" data-label="Loans etc.">{{ $slip->other_deductions > 0 ? number_format($slip->other_deductions, 2) : '—' }}</td>
                <td class="px-3 py-3 text-right font-semibold" data-label="Net">₦{{ number_format($slip->net, 2) }}</td>
                <td class="px-3 py-3 text-right" data-label=""><a href="{{ route('admin.payroll.payslip-pdf', $slip) }}" class="text-primary hover:underline whitespace-nowrap"><i class="fas fa-file-pdf"></i> Payslip</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="bg-white rounded-lg shadow p-5 max-w-xl">
    <h3 class="font-semibold mb-1">Staff cost by site</h3>
    <p class="text-xs text-gray-500 mb-3">Gross pay plus employer pension, split by hours worked at each site (primary site when nobody clocked in).</p>
    <ul class="divide-y text-sm">
        @foreach(collect($siteCosts)->sortDesc() as $siteId => $cost)
        <li class="py-2 flex justify-between"><span>{{ $siteId === 'unallocated' ? 'No site' : ($sites[$siteId] ?? 'Site #' . $siteId) }}</span><span class="font-medium">₦{{ number_format($cost, 2) }}</span></li>
        @endforeach
    </ul>
</div>

@if($run->isDraft())
<form method="POST" action="{{ route('admin.payroll.destroy', $run) }}" class="mt-6" onsubmit="return confirm('Delete this draft pay run?')">
    @csrf @method('DELETE')
    <button type="submit" class="text-sm text-red-600 hover:underline">Delete draft</button>
</form>
@endif
@endsection
