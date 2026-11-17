@extends('layouts.admin')
@section('page_title', 'Payments')
@section('content')
<h2 class="text-2xl font-bold mb-6">All Payments</h2>

<div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
    <table class="min-w-full mobile-card-table">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reservation</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guest</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($payments as $payment)
            <tr>
                <td class="px-6 py-4" data-label="Reservation">{{ $payment->reservation->confirmation_code ?? 'N/A' }}</td>
                <td class="px-6 py-4" data-label="Guest">{{ $payment->user->name ?? 'N/A' }}</td>
                <td class="px-6 py-4" data-label="Amount">₦{{ number_format($payment->amount) }}</td>
                <td class="px-6 py-4" data-label="Method">{{ ucfirst($payment->payment_method) }}</td>
                <td class="px-6 py-4 text-sm" data-label="Reference">{{ $payment->reference ?? 'N/A' }}</td>
                <td class="px-6 py-4" data-label="Status">
                    <span class="px-2 py-1 text-xs rounded-full {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ ucfirst($payment->status) }}
                    </span>
                </td>
                <td class="px-6 py-4" data-label="Date">{{ $payment->created_at->format('M d, Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No payments found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $payments->links() }}
@endsection
