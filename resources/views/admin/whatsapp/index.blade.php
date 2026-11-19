@extends('layouts.admin')
@section('page_title', 'WhatsApp Automated Messaging Hub')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">WhatsApp Automated Messaging Hub</h2>
        <p class="text-sm text-gray-500">Automated guest WhatsApp alerts for booking confirmations, payment receipts, check-in Wi-Fi credentials & checkout reminders.</p>
    </div>
    <div class="flex flex-wrap items-center gap-3" x-data>
        <button @click="$dispatch('open-send-modal')" type="button" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">
            <i class="fab fa-whatsapp text-lg"></i>
            <span>Send WhatsApp Message</span>
        </button>
    </div>
</div>

@if(session('success'))
<div class="mb-6 bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-800 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <i class="fas fa-check-circle text-emerald-600"></i>
        <span>{{ session('success') }}</span>
    </div>
</div>
@endif

@if(session('error'))
<div class="mb-6 bg-rose-50 border border-rose-200 rounded-lg p-4 text-sm text-rose-800 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <i class="fas fa-exclamation-circle text-rose-600"></i>
        <span>{{ session('error') }}</span>
    </div>
</div>
@endif

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Total Dispatched</p>
        <p class="text-2xl font-bold text-slate-800">{{ number_format($stats['total_sent']) }}</p>
        <span class="text-xs text-emerald-600 font-medium">Delivered / Active</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Last 24 Hours</p>
        <p class="text-2xl font-bold text-slate-800">{{ number_format($stats['recent_24h']) }}</p>
        <span class="text-xs text-slate-400">Automated guest notifications</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Delivery Failures</p>
        <p class="text-2xl font-bold {{ $stats['failed'] > 0 ? 'text-rose-600' : 'text-slate-800' }}">{{ number_format($stats['failed']) }}</p>
        <span class="text-xs text-slate-400">{{ $stats['failed'] > 0 ? 'Requires phone verification' : 'Zero delivery errors' }}</span>
    </div>
</div>

<!-- Message Logs Table -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6" x-data="{ modalOpen: false }" @open-send-modal.window="modalOpen = true">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200 text-xs uppercase tracking-wider">
                <tr>
                    <th class="py-3 px-4">Recipient</th>
                    <th class="py-3 px-4">Template</th>
                    <th class="py-3 px-4">Message Preview</th>
                    <th class="py-3 px-4">Gateway</th>
                    <th class="py-3 px-4 text-center">Status</th>
                    <th class="py-3 px-4 text-right">Sent Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="py-3 px-4">
                        <div class="font-medium text-slate-800 font-mono text-xs">{{ $log->recipient_phone }}</div>
                        @if($log->user)
                        <div class="text-xs text-slate-400">{{ $log->user->name }}</div>
                        @elseif($log->reservation)
                        <div class="text-xs text-slate-400">{{ $log->reservation->booking_ref }}</div>
                        @endif
                    </td>
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">
                            {{ ucwords(str_replace('_', ' ', $log->template)) }}
                        </span>
                    </td>
                    <td class="py-3 px-4 max-w-md truncate" title="{{ $log->message }}">
                        <span class="text-xs text-slate-700 line-clamp-2 whitespace-pre-line">{{ $log->message }}</span>
                    </td>
                    <td class="py-3 px-4 text-xs font-mono text-slate-500">
                        {{ ucfirst($log->gateway) }}
                    </td>
                    <td class="py-3 px-4 text-center">
                        @if($log->status === 'sent' || $log->status === 'simulated')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                            <i class="fas fa-check-double mr-1 text-[10px]"></i> Sent
                        </span>
                        @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800" title="{{ $log->error_message }}">
                            <i class="fas fa-exclamation-circle mr-1 text-[10px]"></i> Failed
                        </span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-right text-xs text-slate-400">
                        {{ $log->created_at?->diffForHumans() }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-slate-400">
                        <i class="fab fa-whatsapp text-3xl mb-2 text-slate-300"></i>
                        <p>No WhatsApp messages logged yet. Alerts are sent automatically when guests book, pay, or check in.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $logs->links() }}
    </div>
    @endif

    <!-- Send Modal -->
    <div x-show="modalOpen" x-transition class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-xl shadow-xl border border-slate-200 max-w-lg w-full p-6" @click.outside="modalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                    <i class="fab fa-whatsapp text-emerald-600"></i>
                    Send WhatsApp Message
                </h3>
                <button @click="modalOpen = false" type="button" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.whatsapp.send') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Recipient Phone Number (Nigeria / International)</label>
                    <input type="text" name="recipient_phone" required placeholder="08012345678 or +234..." class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-transparent font-mono">
                    <span class="text-[10px] text-slate-400">Accepts standard Nigerian mobile (080/081/090/070) or international format.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Message Body</label>
                    <textarea name="message" rows="5" required placeholder="Type your WhatsApp notification or update..." class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium shadow-sm transition">
                        <i class="fab fa-whatsapp mr-1"></i> Send Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
