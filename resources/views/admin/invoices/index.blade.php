@extends('layouts.admin')
@section('page_title', 'Invoices')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Invoices</h2>
    <a href="{{ route('admin.invoices.create') }}" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-accent/90">
        <i class="fas fa-plus mr-2"></i>Create Invoice
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guest</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($invoices as $invoice)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-primary hover:underline font-medium">
                        {{ $invoice->invoice_number }}
                    </a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    {{ $invoice->user->name ?? 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    {{ $invoice->reservation->property->name ?? 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ₦{{ number_format($invoice->total_amount, 2) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ₦{{ number_format($invoice->paid_amount, 2) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="{{ $invoice->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                        ₦{{ number_format($invoice->balance, 2) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @php
                    $statusClass = match($invoice->status) {
                        'paid' => 'bg-green-100 text-green-800',
                        'partial' => 'bg-yellow-100 text-yellow-800',
                        'overdue' => 'bg-red-100 text-red-800',
                        'cancelled' => 'bg-gray-100 text-gray-800',
                        default => 'bg-blue-100 text-blue-800',
                    };
                    @endphp
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $invoice->created_at->format('M d, Y') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-primary hover:text-accent mr-2">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('admin.invoices.pdf', $invoice) }}" target="_blank" class="text-gray-600 hover:text-primary mr-2">
                        <i class="fas fa-file-pdf"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                    No invoices found. Create your first invoice.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $invoices->links() }}
</div>
@endsection