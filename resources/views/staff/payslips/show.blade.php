@extends('layouts.staff')
@section('page_title', 'Payslip')
@section('content')
<div class="max-w-3xl">
    <div class="mb-4 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('staff.payslips.index') }}" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-arrow-left mr-1"></i> My payslips</a>
            <h2 class="mt-1 text-xl font-bold">{{ $payslip->payRun->periodLabel() }}</h2>
        </div>
        <a href="{{ route('staff.payslips.pdf', $payslip) }}" class="px-4 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary"><i class="fas fa-file-pdf mr-1"></i> Download PDF</a>
    </div>
    @include('payroll._payslip', ['payslip' => $payslip])
</div>
@endsection
