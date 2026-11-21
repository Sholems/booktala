@extends('layouts.admin')

@section('page_title', 'Email Marketing & Campaigns')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Email Marketing & Retention Suite</h1>
            <p class="text-xs text-gray-500">Design luxury broadcast campaigns, manage customer segments, and power retention via Brevo.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.marketing.subscribers.index') }}" class="px-3.5 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition shadow-sm">
                <i class="fas fa-users mr-1 text-primary"></i> Manage Subscribers ({{ number_format($stats['total_subscribers']) }})
            </a>
            <a href="{{ route('admin.marketing.campaigns.create') }}" class="px-3.5 py-2 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary/90 transition shadow">
                <i class="fas fa-plus mr-1"></i> New Campaign
            </a>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider block mb-1">Active Subscribers</span>
            <p class="text-2xl font-black text-gray-900">{{ number_format($stats['total_subscribers']) }}</p>
            <span class="text-[11px] text-emerald-600 font-medium">Opted-in VIPs & Guests</span>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider block mb-1">Total Campaigns</span>
            <p class="text-2xl font-black text-gray-900">{{ number_format($stats['total_campaigns']) }}</p>
            <span class="text-[11px] text-gray-400 font-medium">Drafts & Broadcasts</span>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider block mb-1">Total Emails Dispatched</span>
            <p class="text-2xl font-black text-primary">{{ number_format($stats['total_emails_sent']) }}</p>
            <span class="text-[11px] text-primary/80 font-medium">Powered by Brevo Relay</span>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider block mb-1">Delivery Protocol</span>
            <p class="text-xl font-bold text-gray-900 flex items-center gap-1.5 mt-1">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                Brevo Active
            </p>
            <span class="text-[11px] text-gray-500 font-mono">Port 465 SSL</span>
        </div>
    </div>

    <!-- Campaigns List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row gap-3 justify-between items-center bg-gray-50/50">
            <h2 class="text-sm font-bold text-gray-800">Campaign History & Scheduled Broadcasts</h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.marketing.campaigns.index') }}" class="px-2.5 py-1 text-xs rounded-lg {{ !request('status') ? 'bg-primary text-white font-bold' : 'bg-white border text-gray-600' }}">All</a>
                <a href="{{ route('admin.marketing.campaigns.index', ['status' => 'draft']) }}" class="px-2.5 py-1 text-xs rounded-lg {{ request('status') === 'draft' ? 'bg-primary text-white font-bold' : 'bg-white border text-gray-600' }}">Drafts</a>
                <a href="{{ route('admin.marketing.campaigns.index', ['status' => 'sent']) }}" class="px-2.5 py-1 text-xs rounded-lg {{ request('status') === 'sent' ? 'bg-primary text-white font-bold' : 'bg-white border text-gray-600' }}">Sent</a>
            </div>
        </div>

        @if($campaigns->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase font-bold text-[10px] tracking-wider">
                        <th class="p-3.5">Campaign Title & Subject</th>
                        <th class="p-3.5">Target Segment</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5">Recipients / Sent</th>
                        <th class="p-3.5">Date</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($campaigns as $campaign)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="p-3.5">
                            <a href="{{ route('admin.marketing.campaigns.show', $campaign) }}" class="font-bold text-gray-900 hover:text-primary transition text-sm block">
                                {{ $campaign->title }}
                            </a>
                            <span class="text-gray-500 text-xs flex items-center gap-1 mt-0.5">
                                <i class="fas fa-envelope text-gray-400"></i> {{ $campaign->subject }}
                            </span>
                        </td>
                        <td class="p-3.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                {{ ucwords(str_replace('_', ' ', $campaign->target_segment)) }}
                            </span>
                        </td>
                        <td class="p-3.5">
                            @if($campaign->status === 'sent')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                    <i class="fas fa-check-circle mr-1"></i> Sent
                                </span>
                            @elseif($campaign->status === 'sending')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 animate-pulse">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Sending
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                    Draft
                                </span>
                            @endif
                        </td>
                        <td class="p-3.5">
                            @if($campaign->status === 'sent')
                                <span class="font-bold text-gray-900">{{ number_format($campaign->sent_count) }}</span>
                                <span class="text-gray-400">/ {{ number_format($campaign->recipient_count) }}</span>
                                @if($campaign->failed_count > 0)
                                    <span class="text-red-500 font-semibold text-[11px]">({{ $campaign->failed_count }} failed)</span>
                                @endif
                            @else
                                <span class="text-gray-400 italic">Est. {{ number_format($stats['segment_counts'][$campaign->target_segment] ?? 0) }} contacts</span>
                            @endif
                        </td>
                        <td class="p-3.5 text-gray-500">
                            {{ $campaign->sent_at ? $campaign->sent_at->format('M d, Y · h:i A') : $campaign->created_at->format('M d, Y') }}
                        </td>
                        <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                            <a href="{{ route('admin.marketing.campaigns.show', $campaign) }}" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold inline-block">
                                Details
                            </a>
                            @if($campaign->status === 'draft')
                            <a href="{{ route('admin.marketing.campaigns.edit', $campaign) }}" class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-semibold inline-block">
                                Edit
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200">
            {{ $campaigns->links() }}
        </div>
        @else
        <div class="p-12 text-center">
            <div class="w-16 h-16 bg-primary/10 text-primary rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fas fa-paper-plane"></i>
            </div>
            <h3 class="text-base font-bold text-gray-800 mb-1">No Email Campaigns Created Yet</h3>
            <p class="text-xs text-gray-500 max-w-sm mx-auto mb-6">Create your first luxury email campaign to announce new villas, launch seasonal promotions, or engage past guests.</p>
            <a href="{{ route('admin.marketing.campaigns.create') }}" class="px-4 py-2 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary/90 transition shadow inline-flex items-center gap-2">
                <i class="fas fa-plus"></i> Compose First Campaign
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
