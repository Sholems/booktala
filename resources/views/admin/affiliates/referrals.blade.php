@extends('layouts.admin')

@section('page_title', 'All Affiliate Referrals')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">All Affiliate Referrals</h1>
            <p class="text-xs text-gray-500">Global ledger of every reservation attributed to an affiliate partner.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.affiliates.index') }}" class="px-3.5 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition">
                ← Affiliates Directory
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50/50">
            <form action="{{ route('admin.affiliates.referrals') }}" method="GET" class="flex gap-2">
                <select name="status" class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Escrow</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Matured (Approved)</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid Out</option>
                    <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
                </select>
                <button type="submit" class="px-3 py-2 bg-gray-800 text-white text-xs font-bold rounded-lg hover:bg-gray-700">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="p-4">Booking Ref</th>
                        <th class="p-4">Affiliate Partner</th>
                        <th class="p-4">Apartment</th>
                        <th class="p-4">Stay Dates</th>
                        <th class="p-4">Room Base</th>
                        <th class="p-4">Commission</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Matured At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($referrals as $ref)
                    <tr class="hover:bg-gray-50/50">
                        <td class="p-4 font-mono font-bold text-primary">
                            {{ $ref->reservation->booking_ref }}
                        </td>
                        <td class="p-4">
                            <span class="font-bold text-gray-900 block">{{ $ref->affiliate->user->name ?? 'User' }}</span>
                            <span class="font-mono text-xs text-primary font-bold">[{{ $ref->affiliate->code }}]</span>
                        </td>
                        <td class="p-4 font-medium text-gray-900">
                            {{ $ref->reservation->property->title ?? 'Apartment' }}
                        </td>
                        <td class="p-4 text-xs text-gray-600">
                            {{ $ref->check_in->format('M d') }} – {{ $ref->check_out->format('M d, Y') }}
                        </td>
                        <td class="p-4 font-mono text-gray-700">
                            &#8358;{{ number_format($ref->base_accommodation_amount) }}
                        </td>
                        <td class="p-4 font-mono font-bold text-emerald-600">
                            &#8358;{{ number_format($ref->commission_amount) }} ({{ number_format($ref->commission_rate, 0) }}%)
                        </td>
                        <td class="p-4">
                            @if($ref->status === 'approved')
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Matured</span>
                            @elseif($ref->status === 'paid')
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">Paid</span>
                            @elseif($ref->status === 'void')
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-600" title="{{ $ref->void_reason }}">Void</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Pending</span>
                            @endif
                        </td>
                        <td class="p-4 text-xs text-gray-500">
                            {{ $ref->matured_at ? $ref->matured_at->format('M d, Y') : 'N/A' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-gray-500">
                            No referrals matching this criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($referrals->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{ $referrals->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
