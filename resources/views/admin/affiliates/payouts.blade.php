@extends('layouts.admin')

@section('page_title', 'Affiliate Payout Queue')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Affiliate Payout Queue</h1>
            <p class="text-xs text-gray-500">Review partner withdrawal requests and record electronic settlements.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.affiliates.index') }}" class="px-3.5 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition">
                ← Affiliates Directory
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50/50">
            <form action="{{ route('admin.affiliates.payouts') }}" method="GET" class="flex gap-2">
                <select name="status" class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="requested" {{ request('status') === 'requested' ? 'selected' : '' }}>Pending Review</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Settled (Completed)</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Declined (Failed)</option>
                </select>
                <button type="submit" class="px-3 py-2 bg-gray-800 text-white text-xs font-bold rounded-lg hover:bg-gray-700">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="p-4">Reference</th>
                        <th class="p-4">Partner</th>
                        <th class="p-4">Amount</th>
                        <th class="p-4">Bank Destination</th>
                        <th class="p-4">Requested At</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Actions / Settlement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payouts as $payout)
                    <tr class="hover:bg-gray-50/50">
                        <td class="p-4 font-mono font-bold text-primary">
                            {{ $payout->payout_ref }}
                        </td>
                        <td class="p-4">
                            <span class="font-bold text-gray-900 block">{{ $payout->affiliate->user->name ?? 'User' }}</span>
                            <span class="font-mono text-xs text-primary font-bold">[{{ $payout->affiliate->code }}]</span>
                        </td>
                        <td class="p-4 font-mono font-bold text-gray-900 text-base">
                            &#8358;{{ number_format($payout->amount) }}
                        </td>
                        <td class="p-4 text-xs">
                            <strong class="text-gray-900 block">{{ $payout->bank_name }}</strong>
                            <span class="font-mono text-gray-700 block">{{ $payout->account_number }}</span>
                            <span class="text-gray-500">{{ $payout->account_name }}</span>
                        </td>
                        <td class="p-4 text-xs text-gray-500">
                            {{ $payout->created_at->format('M d, Y H:i') }}
                        </td>
                        <td class="p-4">
                            @if($payout->status === 'completed')
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                Settled
                            </span>
                            @if($payout->transfer_reference)
                            <span class="block text-[10px] text-gray-500 font-mono mt-1">Ref: {{ $payout->transfer_reference }}</span>
                            @endif
                            @elseif($payout->status === 'failed')
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                Declined
                            </span>
                            @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                Needs Approval
                            </span>
                            @endif
                        </td>
                        <td class="p-4 text-xs">
                            @if($payout->status === 'requested')
                            <div class="flex flex-col gap-2">
                                <form action="{{ route('admin.affiliates.payouts.complete', $payout->id) }}" method="POST" class="flex gap-1.5 items-center">
                                    @csrf
                                    <input type="text" name="transfer_reference" required placeholder="Bank Wire / Trx Ref" class="px-2.5 py-1 text-xs border border-gray-300 rounded focus:outline-none w-36 font-mono">
                                    <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold transition">
                                        Mark Paid
                                    </button>
                                </form>

                                <form action="{{ route('admin.affiliates.payouts.reject', $payout->id) }}" method="POST" onsubmit="return confirm('Decline this payout and refund the balance back to the partner?');">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:underline text-[11px] font-semibold">
                                        Decline & Refund Balance
                                    </button>
                                </form>
                            </div>
                            @else
                            <span class="text-gray-400">Processed</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-500">
                            No payout requests in this queue.
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
