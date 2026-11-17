@extends('layouts.admin')
@section('page_title', 'Invoice ' . $invoice->invoice_number)
@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <a href="{{ route('admin.invoices.index') }}" class="text-primary hover:underline text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Invoices
        </a>
        <h2 class="text-2xl font-bold mt-2">Invoice {{ $invoice->invoice_number }}</h2>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.invoices.pdf', $invoice) }}" target="_blank" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
            <i class="fas fa-file-pdf mr-2"></i>Download PDF
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-lg font-semibold">{{ $siteName ?? 'BookTala' }}</h3>
                    <p class="text-sm text-gray-500"> {{ \App\Models\Setting::get('email', config('brand.email')) }}</p>
                    <p class="text-sm text-gray-500"> {{ \App\Models\Setting::get('phone', config('brand.phone')) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Invoice Number</p>
                    <p class="font-semibold">{{ $invoice->invoice_number }}</p>
                    <p class="text-sm text-gray-500 mt-2">Date</p>
                    <p class="font-semibold">{{ $invoice->created_at->format('M d, Y') }}</p>
                    @if($invoice->due_date)
                    <p class="text-sm text-gray-500 mt-2">Due Date</p>
                    <p class="font-semibold">{{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}</p>
                    @endif
                </div>
            </div>

            <div class="mb-6">
                <h4 class="text-sm font-semibold text-gray-500 mb-2">Bill To</h4>
                <p class="font-medium">{{ $invoice->user->name ?? 'N/A' }}</p>
                <p class="text-sm text-gray-500">{{ $invoice->user->email ?? '' }}</p>
                <p class="text-sm text-gray-500">{{ $invoice->user->phone ?? '' }}</p>
            </div>

            <div class="mb-6">
                <h4 class="text-sm font-semibold text-gray-500 mb-2">Reservation Details</h4>
                <p class="font-medium">{{ $invoice->reservation->property->name ?? 'N/A' }}</p>
                <p class="text-sm text-gray-500">
                    Check-in: {{ \Carbon\Carbon::parse($invoice->reservation->check_in)->format('M d, Y') }}
                </p>
                <p class="text-sm text-gray-500">
                    Check-out: {{ \Carbon\Carbon::parse($invoice->reservation->check_out)->format('M d, Y') }}
                </p>
                <p class="text-sm text-gray-500">
                    {{ $invoice->reservation->nights ?? 0 }} nights
                </p>
            </div>

            <table class="min-w-full mb-6">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Description</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Qty</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Unit Price</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($invoice->items as $item)
                    <tr>
                        <td class="px-4 py-2">{{ $item->description }}</td>
                        <td class="px-4 py-2 text-right">{{ $item->quantity }}</td>
                        <td class="px-4 py-2 text-right">₦{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-4 py-2 text-right">₦{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-4 text-center text-gray-500">No items</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="border-t pt-4">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Subtotal</span>
                    <span>₦{{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                @if($invoice->vat_amount > 0)
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">VAT ({{ \App\Models\Setting::get('vat_rate', 7.5) }}%)</span>
                    <span>₦{{ number_format($invoice->vat_amount, 2) }}</span>
                </div>
                @endif
                @if($invoice->discount_amount > 0)
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Discount</span>
                    <span class="text-green-600">-₦{{ number_format($invoice->discount_amount, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between font-semibold text-lg mt-2 pt-2 border-t">
                    <span>Total</span>
                    <span>₦{{ number_format($invoice->total_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-green-600 mt-2">
                    <span>Paid</span>
                    <span>₦{{ number_format($invoice->paid_amount, 2) }}</span>
                </div>
                <div class="flex justify-between font-semibold text-red-600 mt-2">
                    <span>Balance Due</span>
                    <span>₦{{ number_format($invoice->balance, 2) }}</span>
                </div>
            </div>

            @if($invoice->notes)
            <div class="mt-6 p-4 bg-gray-50 rounded">
                <h4 class="text-sm font-semibold mb-1">Notes</h4>
                <p class="text-sm text-gray-600">{{ $invoice->notes }}</p>
            </div>
            @endif
        </div>
    </div>

    <div>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="font-semibold mb-4">Status</h3>
            @php
            $statusClass = match($invoice->status) {
                'paid' => 'bg-green-100 text-green-800',
                'partial' => 'bg-yellow-100 text-yellow-800',
                'overdue' => 'bg-red-100 text-red-800',
                default => 'bg-blue-100 text-blue-800',
            };
            @endphp
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusClass }}">
                {{ ucfirst($invoice->status) }}
            </span>

            @if($invoice->status != 'paid')
            <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}" class="mt-4">
                @csrf
                <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Mark as Paid
                </button>
            </form>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="font-semibold mb-4">Send Invoice</h3>
            <form method="POST" action="{{ route('admin.invoices.send', $invoice) }}">
                @csrf
                <div class="mb-3">
                    <input type="email" name="email" value="{{ $invoice->user->email ?? '' }}" 
                           class="w-full border rounded px-3 py-2" placeholder="Email address" required>
                </div>
                <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded hover:bg-primary/90">
                    <i class="fas fa-paper-plane mr-2"></i>Send Invoice
                </button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-4">Payment History</h3>
            @forelse($invoice->payments as $payment)
            <div class="border-b pb-3 mb-3 last:border-0">
                <p class="font-medium">₦{{ number_format($payment->amount, 2) }}</p>
                <p class="text-sm text-gray-500">{{ $payment->created_at->format('M d, Y') }}</p>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $payment->status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ ucfirst($payment->status) }}
                </span>
            </div>
            @empty
            <p class="text-gray-500 text-sm">No payments recorded</p>
            @endforelse
        </div>
    </div>
</div>
@endsection