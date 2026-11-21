@extends('layouts.admin')

@section('page_title', 'Partner Details: ' . $affiliate->code)

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.affiliates.index') }}" class="text-xs text-primary font-bold hover:underline mb-1 inline-block">
                ← Back to Affiliates
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Partner: {{ $affiliate->user->name ?? 'User #' . $affiliate->user_id }}</h1>
            <p class="text-xs text-gray-500 font-mono">Code: {{ $affiliate->code }} | Referral Link: {{ $affiliate->referral_url }}</p>
        </div>
    </div>

    <!-- Quick Edit & Bank Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Settings Form (6 cols) -->
        <div class="lg:col-span-6 bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h3 class="font-bold text-base text-gray-900 mb-4">Partner Configuration</h3>
            <form action="{{ route('admin.affiliates.update', $affiliate->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Commission Rate (%)</label>
                        <input type="number" step="0.5" min="1" max="50" name="commission_rate" value="{{ old('commission_rate', $affiliate->commission_rate) }}" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none">
                            <option value="active" {{ $affiliate->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="pending" {{ $affiliate->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="suspended" {{ $affiliate->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary/90 transition">
                    Save Partner Settings
                </button>
            </form>
        </div>

        <!-- Bank & Earnings Info (6 cols) -->
        <div class="lg:col-span-6 bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h3 class="font-bold text-base text-gray-900 mb-3">Settlement & Financial Snapshot</h3>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1.5 border-b border-gray-100">
                    <span class="text-gray-500">Available Balance:</span>
                    <span class="font-mono font-bold text-emerald-600 text-sm">&#8358;{{ number_format($affiliate->current_balance) }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-gray-100">
                    <span class="text-gray-500">Pending In Escrow:</span>
                    <span class="font-mono font-bold text-amber-600">&#8358;{{ number_format($affiliate->pending_balance) }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-gray-100">
                    <span class="text-gray-500">Lifetime Earnings:</span>
                    <span class="font-mono font-bold text-gray-900">&#8358;{{ number_format($affiliate->lifetime_earnings) }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-gray-100">
                    <span class="text-gray-500">Bank Destination:</span>
                    <span class="font-bold text-gray-900">{{ $affiliate->bank_name ?: 'Not configured' }}</span>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-gray-500">NUBAN / Name:</span>
                    <span class="font-mono text-gray-700">{{ $affiliate->account_number }} ({{ $affiliate->account_name }})</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Referrals Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-bold text-gray-900 text-base">Referrals Attributed to this Partner</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="p-4">Booking Ref</th>
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
                            &#8358;{{ number_format($ref->commission_amount) }}
                        </td>
                        <td class="p-4">
                            @if($ref->status === 'approved')
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Matured</span>
                            @elseif($ref->status === 'paid')
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">Paid</span>
                            @elseif($ref->status === 'void')
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-600">Void</span>
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
                        <td colspan="7" class="p-6 text-center text-gray-500">
                            No referrals logged yet for this partner.
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
