@extends('layouts.admin')

@section('page_title', 'Affiliate Management')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Affiliate & Partner Program</h1>
            <p class="text-xs text-gray-500">Manage 5% direct commission affiliates, track performance, and approve payouts.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.affiliates.referrals') }}" class="px-3.5 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition">
                <i class="fas fa-receipt mr-1 text-emerald-600"></i> All Referrals
            </a>
            <a href="{{ route('admin.affiliates.payouts') }}" class="px-3.5 py-2 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary/90 transition shadow">
                <i class="fas fa-wallet mr-1 text-amber-400"></i> Payout Queue
            </a>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider block mb-1">Total Active Partners</span>
            <p class="text-2xl font-black text-gray-900">{{ number_format($totalAffiliates) }}</p>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider block mb-1">Total Paid Out</span>
            <p class="text-2xl font-black text-emerald-600">&#8358;{{ number_format($totalPaidOut) }}</p>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider block mb-1">Pending Commissions (Escrow)</span>
            <p class="text-2xl font-black text-amber-600">&#8358;{{ number_format($totalPendingCommissions) }}</p>
        </div>
    </div>

    <!-- Filters & Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row gap-3 justify-between items-center bg-gray-50/50">
            <form action="{{ route('admin.affiliates.index') }}" method="GET" class="flex flex-1 gap-2 w-full sm:w-auto">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search partner code, name, email or bank..." class="w-full sm:w-72 px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none">
                <select name="status" class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
                <button type="submit" class="px-3 py-2 bg-gray-800 text-white text-xs font-bold rounded-lg hover:bg-gray-700">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="p-4">Partner</th>
                        <th class="p-4">Code</th>
                        <th class="p-4">Rate</th>
                        <th class="p-4">Referrals</th>
                        <th class="p-4">Available Balance</th>
                        <th class="p-4">Lifetime Earned</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($affiliates as $affiliate)
                    <tr class="hover:bg-gray-50/50">
                        <td class="p-4">
                            <span class="font-bold text-gray-900 block">{{ $affiliate->user->name ?? 'User #' . $affiliate->user_id }}</span>
                            <span class="text-xs text-gray-500 font-mono">{{ $affiliate->user->email ?? 'N/A' }}</span>
                        </td>
                        <td class="p-4 font-mono font-bold text-primary">
                            {{ $affiliate->code }}
                        </td>
                        <td class="p-4 text-xs font-semibold">
                            {{ number_format($affiliate->commission_rate, 1) }}%
                        </td>
                        <td class="p-4 text-xs font-semibold">
                            {{ $affiliate->referrals_count }} stay(s)
                        </td>
                        <td class="p-4 font-mono font-bold text-emerald-600">
                            &#8358;{{ number_format($affiliate->current_balance) }}
                        </td>
                        <td class="p-4 font-mono text-gray-700">
                            &#8358;{{ number_format($affiliate->lifetime_earnings) }}
                        </td>
                        <td class="p-4">
                            @if($affiliate->status === 'active')
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">Active</span>
                            @elseif($affiliate->status === 'suspended')
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-100 text-red-800">Suspended</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">Pending</span>
                            @endif
                        </td>
                        <td class="p-4 text-xs">
                            <a href="{{ route('admin.affiliates.show', $affiliate->id) }}" class="px-3 py-1 bg-gray-100 hover:bg-primary hover:text-white rounded font-bold transition">
                                Manage
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-gray-500">
                            No affiliate partners found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($affiliates->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{ $affiliates->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
