@extends('layouts.affiliate')

@section('title', 'Payouts & Banking')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-gray-900">Payouts & Banking</h1>
        <p class="text-xs md:text-sm text-gray-500">Manage your Nigerian bank details and request payouts for matured commissions.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Request Payout Card (Left / 7 cols) -->
        <div class="lg:col-span-7 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="font-bold text-lg text-gray-900 mb-1 flex items-center gap-2">
                <i class="fas fa-hand-holding-dollar text-emerald-600"></i> Request Withdrawal
            </h3>
            <p class="text-xs text-gray-500 mb-6">Matured commissions can be transferred directly to your verified Nigerian bank account.</p>

            <div class="p-4 bg-gray-50 rounded-xl mb-6 flex items-center justify-between border border-gray-200">
                <div>
                    <span class="text-xs text-gray-500 block">Available for Immediate Payout</span>
                    <span class="text-2xl font-black text-emerald-600">&#8358;{{ number_format($affiliate->current_balance) }}</span>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-500 block">Minimum Withdrawal</span>
                    <span class="text-sm font-bold text-gray-700">&#8358;{{ number_format($minimumPayout) }}</span>
                </div>
            </div>

            @if(empty($affiliate->account_number) || empty($affiliate->bank_name))
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs mb-4">
                <i class="fas fa-exclamation-circle mr-1"></i> Please update your bank details on the right before requesting a payout.
            </div>
            @endif

            <form action="{{ route('affiliate.payouts.request') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                        Withdrawal Amount (&#8358;)
                    </label>
                    <input type="number" name="amount" min="{{ $minimumPayout }}" max="{{ $affiliate->current_balance }}" step="1000" value="{{ old('amount', $affiliate->current_balance >= $minimumPayout ? $affiliate->current_balance : $minimumPayout) }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-lg font-bold font-mono text-gray-900 focus:ring-2 focus:ring-primary focus:outline-none" {{ $affiliate->current_balance < $minimumPayout ? 'disabled' : '' }}>
                    <p class="text-[11px] text-gray-500 mt-1">Transfers are processed within 24 hours via Nigerian electronic bank wire.</p>
                </div>

                <button type="submit" class="w-full py-3 bg-emerald-600 text-white font-bold rounded-xl shadow hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2" {{ ($affiliate->current_balance < $minimumPayout || empty($affiliate->account_number)) ? 'disabled' : '' }}>
                    <i class="fas fa-paper-plane"></i> Submit Payout Request
                </button>
            </form>
        </div>

        <!-- Bank Details Card (Right / 5 cols) -->
        <div class="lg:col-span-5 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="font-bold text-lg text-gray-900 mb-1 flex items-center gap-2">
                <i class="fas fa-university text-primary"></i> Nigerian Bank Details
            </h3>
            <p class="text-xs text-gray-500 mb-6">Enter your 10-digit NUBAN account where funds will be credited.</p>

            <form action="{{ route('affiliate.bank.update') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Bank Name</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $affiliate->bank_name) }}" required placeholder="e.g. GTBank, Zenith, Access" class="w-full px-3.5 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Account Number (10 digits)</label>
                        <input type="text" name="account_number" value="{{ old('account_number', $affiliate->account_number) }}" required maxlength="10" placeholder="0123456789" class="w-full px-3.5 py-2 border border-gray-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Account Name</label>
                        <input type="text" name="account_name" value="{{ old('account_name', $affiliate->account_name) }}" required placeholder="Account Holder Full Name" class="w-full px-3.5 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary/90 transition shadow">
                        Save Bank Details
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payout Request History -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-200">
            <h3 class="font-bold text-gray-900 text-base">Withdrawal History</h3>
            <p class="text-xs text-gray-500">Record of all payout requests and settlements</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="p-4">Reference</th>
                        <th class="p-4">Date</th>
                        <th class="p-4">Amount</th>
                        <th class="p-4">Bank Destination</th>
                        <th class="p-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payouts as $payout)
                    <tr class="hover:bg-gray-50/50">
                        <td class="p-4 font-mono font-bold text-primary">
                            {{ $payout->payout_ref }}
                        </td>
                        <td class="p-4 text-xs text-gray-600">
                            {{ $payout->created_at->format('M d, Y H:i') }}
                        </td>
                        <td class="p-4 font-mono font-bold text-gray-900">
                            &#8358;{{ number_format($payout->amount) }}
                        </td>
                        <td class="p-4 text-xs">
                            <span class="font-bold text-gray-900">{{ $payout->bank_name }}</span>
                            <span class="block text-gray-500 font-mono">{{ $payout->account_number }} ({{ $payout->account_name }})</span>
                        </td>
                        <td class="p-4">
                            @if($payout->status === 'completed')
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                Paid
                            </span>
                            @elseif($payout->status === 'processing')
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                Processing
                            </span>
                            @elseif($payout->status === 'failed')
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                Failed
                            </span>
                            @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                Pending Approval
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500">
                            No payout requests made yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payouts->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{ $payouts->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
