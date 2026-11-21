@extends('layouts.admin')

@section('page_title', 'Campaign: ' . $campaign->title)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.marketing.campaigns.index') }}" class="text-xs font-bold text-gray-500 hover:text-primary">
                    <i class="fas fa-arrow-left mr-1"></i> Campaigns
                </a>
                <span class="text-gray-300">/</span>
                <span class="text-xs font-bold text-gray-700">Details</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                {{ $campaign->title }}
                @if($campaign->status === 'sent')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                        <i class="fas fa-check-circle mr-1"></i> Sent
                    </span>
                @elseif($campaign->status === 'sending')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 animate-pulse">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Sending
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                        Draft
                    </span>
                @endif
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.marketing.campaigns.preview', $campaign) }}" target="_blank" class="px-3.5 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition shadow-sm">
                <i class="fas fa-eye mr-1 text-primary"></i> Live Email Preview
            </a>

            @if($campaign->status === 'draft')
            <a href="{{ route('admin.marketing.campaigns.edit', $campaign) }}" class="px-3.5 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition shadow-sm">
                <i class="fas fa-pencil-alt mr-1 text-amber-500"></i> Edit
            </a>

            <form action="{{ route('admin.marketing.campaigns.dispatch', $campaign) }}" method="POST" onsubmit="return confirm('Are you sure you want to dispatch this campaign to ALL contacts in this segment? This will send real emails via Brevo.');">
                @csrf
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow flex items-center gap-1.5">
                    <i class="fas fa-paper-plane"></i> Dispatch Campaign Now
                </button>
            </form>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-medium flex items-center gap-2">
        <i class="fas fa-check-circle text-emerald-600 text-sm"></i> {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-xs font-medium flex items-center gap-2">
        <i class="fas fa-exclamation-circle text-red-600 text-sm"></i> {{ session('error') }}
    </div>
    @endif

    <!-- Grid Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Summary & Metadata -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-4">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-2">
                    Campaign Specifications
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-gray-400 block font-medium">Subject Line</span>
                        <span class="font-bold text-gray-900 text-sm mt-0.5 block">{{ $campaign->subject }}</span>
                    </div>

                    <div>
                        <span class="text-gray-400 block font-medium">Target Audience</span>
                        <span class="font-bold text-primary mt-0.5 block">
                            {{ ucwords(str_replace('_', ' ', $campaign->target_segment)) }}
                            <span class="text-gray-400 font-normal">({{ number_format($segmentCounts[$campaign->target_segment] ?? 0) }} contacts)</span>
                        </span>
                    </div>

                    @if($campaign->preview_text)
                    <div class="sm:col-span-2">
                        <span class="text-gray-400 block font-medium">Preheader Snippet</span>
                        <span class="text-gray-700 italic mt-0.5 block">{{ $campaign->preview_text }}</span>
                    </div>
                    @endif

                    @if($campaign->discount_code)
                    <div>
                        <span class="text-gray-400 block font-medium">Promo Voucher Code</span>
                        <span class="font-mono font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 inline-block mt-0.5">
                            {{ $campaign->discount_code }}
                        </span>
                    </div>
                    @endif

                    @if($campaign->cta_text)
                    <div>
                        <span class="text-gray-400 block font-medium">Primary CTA</span>
                        <a href="{{ $campaign->cta_url }}" target="_blank" class="text-primary hover:underline font-semibold block truncate mt-0.5">
                            {{ $campaign->cta_text }} →
                        </a>
                    </div>
                    @endif
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <span class="text-gray-400 block text-xs font-medium mb-1.5">Editorial Content Body</span>
                    <div class="bg-gray-50 p-4 rounded-xl text-xs text-gray-700 leading-relaxed font-sans whitespace-pre-line border border-gray-100">
                        {{ $campaign->content_html }}
                    </div>
                </div>
            </div>

            <!-- Delivery Activity Logs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">
                        Recipient Delivery Log (Latest 50)
                    </h3>
                    <span class="text-[11px] text-gray-400">Total logs: {{ $campaign->logs->count() }}</span>
                </div>

                @if($campaign->logs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] font-bold border-b border-gray-200">
                            <tr>
                                <th class="p-3">Recipient</th>
                                <th class="p-3">Type</th>
                                <th class="p-3">Delivery Status</th>
                                <th class="p-3 text-right">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($campaign->logs as $log)
                            <tr>
                                <td class="p-3">
                                    <span class="font-bold text-gray-900 block">{{ $log->recipient_email }}</span>
                                    @if($log->recipient_name)
                                        <span class="text-gray-400 text-[11px]">{{ $log->recipient_name }}</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <span class="text-[11px] text-gray-600">{{ $log->recipient_type }}</span>
                                </td>
                                <td class="p-3">
                                    @if($log->status === 'sent')
                                        <span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full text-[11px]">
                                            <i class="fas fa-check mr-0.5"></i> Delivered
                                        </span>
                                    @else
                                        <span class="text-red-700 font-bold bg-red-50 px-2 py-0.5 rounded-full text-[11px]" title="{{ $log->error_message }}">
                                            <i class="fas fa-times mr-0.5"></i> Failed
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-right text-gray-500">
                                    {{ $log->sent_at ? $log->sent_at->format('M d, Y · h:i A') : '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="p-8 text-center text-xs text-gray-400">
                    No recipients logged yet. Dispatch the campaign to initiate delivery.
                </div>
                @endif
            </div>
        </div>

        <!-- Right: Actions & Test Box -->
        <div class="space-y-6">
            <!-- Test Send Card -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs">
                        <i class="fas fa-vial"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Send Test Email</h3>
                        <p class="text-[11px] text-gray-500">Preview the exact layout in your personal inbox.</p>
                    </div>
                </div>

                <form action="{{ route('admin.marketing.campaigns.test', $campaign) }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 mb-1">Destination Address</label>
                        <input type="email" name="test_email" value="{{ auth()->user()->email }}" required class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none">
                    </div>
                    <button type="submit" class="w-full py-2 bg-gray-900 hover:bg-black text-white rounded-lg text-xs font-bold transition shadow-sm flex items-center justify-center gap-1.5">
                        <i class="fas fa-paper-plane text-amber-400"></i> Dispatch Test to Inbox
                    </button>
                </form>
            </div>

            <!-- Stats Card -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-3">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-2">
                    Campaign Statistics
                </h3>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Recipients targeted:</span>
                        <span class="font-bold text-gray-900">{{ number_format($campaign->recipient_count) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Successfully sent:</span>
                        <span class="font-bold text-emerald-600">{{ number_format($campaign->sent_count) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Failed / bounced:</span>
                        <span class="font-bold text-red-500">{{ number_format($campaign->failed_count) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-2">
                        <span class="text-gray-500">Relay Service:</span>
                        <span class="font-bold text-gray-700">Brevo SMTP</span>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            @if($campaign->status === 'draft')
            <div class="bg-white p-6 rounded-xl shadow-sm border border-red-100 space-y-3">
                <h3 class="text-xs font-bold text-red-600 uppercase tracking-wider">
                    Danger Zone
                </h3>
                <form action="{{ route('admin.marketing.campaigns.destroy', $campaign) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this campaign?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg text-xs font-bold border border-red-200 transition">
                        Delete Draft Campaign
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
