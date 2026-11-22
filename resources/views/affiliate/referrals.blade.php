@extends('layouts.affiliate')

@section('title', 'Referrals Ledger')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-900">Referrals Ledger</h1>
            <p class="text-xs md:text-sm text-gray-500">Comprehensive audit of all reservations made through your tracking link.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs bg-white px-3 py-1.5 rounded-xl border border-gray-200 font-semibold text-gray-600">
                Total Stays: <strong class="text-primary">{{ $referrals->total() }}</strong>
            </span>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="p-4">Booking Ref</th>
                        <th class="p-4">Apartment</th>
                        <th class="p-4">Stay Dates</th>
                        <th class="p-4">Net Room Base</th>
                        <th class="p-4">Rate</th>
                        <th class="p-4">Your Commission</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Maturation Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($referrals as $ref)
                    <tr class="hover:bg-gray-50/50">
                        <td class="p-4 font-mono font-bold text-primary">
                            {{ $ref->reservation->booking_ref }}
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
                        <td class="p-4 font-semibold text-gray-500 text-xs">
                            {{ number_format($ref->commission_rate, 0) }}%
                        </td>
                        <td class="p-4 font-mono font-bold text-emerald-600">
                            +&#8358;{{ number_format($ref->commission_amount) }}
                        </td>
                        <td class="p-4">
                            @if($ref->status === 'approved')
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                Matured
                            </span>
                            @elseif($ref->status === 'paid')
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                Paid Out
                            </span>
                            @elseif($ref->status === 'void')
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600" title="{{ $ref->void_reason }}">
                                Void
                            </span>
                            @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                Pending
                            </span>
                            @endif
                        </td>
                        <td class="p-4 text-xs text-gray-500">
                            {{ $ref->matured_at ? $ref->matured_at->format('M d, Y H:i') : 'N/A' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-12 text-center text-gray-500">
                            <i class="fas fa-receipt text-4xl text-gray-300 mb-3 block"></i>
                            No referrals logged yet. Share your link to start receiving {{ (int) ($affiliate->commission_rate ?? \App\Services\AffiliateCommissionService::getDefaultCommissionRate()) }}% commission!
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
