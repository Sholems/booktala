@extends('layouts.staff')
@section('page_title', 'My Payslips')
@section('content')
<div class="max-w-3xl">
    <h2 class="text-xl font-bold mb-4">My payslips</h2>
    <div class="bg-white rounded-lg shadow divide-y">
        @forelse($payslips as $payslip)
        <a href="{{ route('staff.payslips.show', $payslip) }}" class="flex items-center justify-between p-4 hover:bg-gray-50">
            <div>
                <div class="font-medium">{{ $payslip->payRun->periodLabel() }}</div>
                <div class="text-xs text-gray-500">{{ $payslip->payRun->status === 'paid' ? 'Paid ' . $payslip->payRun->paid_at?->format('j M Y') : 'Approved, payment pending' }}</div>
            </div>
            <span class="font-semibold">₦{{ number_format($payslip->net, 2) }}</span>
        </a>
        @empty
        <p class="p-6 text-center text-gray-500">No payslips yet.</p>
        @endforelse
    </div>
</div>
@endsection
