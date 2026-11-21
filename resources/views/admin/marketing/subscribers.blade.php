@extends('layouts.admin')

@section('page_title', 'Marketing Subscribers')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.marketing.campaigns.index') }}" class="text-xs font-bold text-gray-500 hover:text-primary">
                    <i class="fas fa-arrow-left mr-1"></i> Email Campaigns
                </a>
                <span class="text-gray-300">/</span>
                <span class="text-xs font-bold text-gray-700">Subscribers</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Newsletter & VIP Club Subscribers</h1>
            <p class="text-xs text-gray-500">Manage opted-in contacts, export audience, and monitor unsubscribes.</p>
        </div>
        <button onclick="document.getElementById('addSubscriberModal').classList.remove('hidden')" class="px-3.5 py-2 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary/90 transition shadow flex items-center gap-1.5">
            <i class="fas fa-user-plus"></i> Add Subscriber Manually
        </button>
    </div>

    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-medium flex items-center gap-2">
        <i class="fas fa-check-circle text-emerald-600 text-sm"></i> {{ session('success') }}
    </div>
    @endif

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider block mb-1">Total Subscribers</span>
            <p class="text-2xl font-black text-gray-900">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider block mb-1">Active (Subscribed)</span>
            <p class="text-2xl font-black text-emerald-600">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider block mb-1">Unsubscribed</span>
            <p class="text-2xl font-black text-red-500">{{ number_format($stats['unsubscribed']) }}</p>
        </div>
    </div>

    <!-- Filter & Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row gap-3 justify-between items-center bg-gray-50/50">
            <form action="{{ route('admin.marketing.subscribers.index') }}" method="GET" class="flex flex-1 gap-2 w-full sm:w-auto">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search email or name..." class="w-full sm:w-72 px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none">
                <select name="status" class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="subscribed" {{ request('status') === 'subscribed' ? 'selected' : '' }}>Active (Subscribed)</option>
                    <option value="unsubscribed" {{ request('status') === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
                </select>
                <button type="submit" class="px-3 py-2 bg-gray-800 text-white text-xs font-bold rounded-lg hover:bg-gray-700">Filter</button>
            </form>
        </div>

        @if($subscribers->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase font-bold text-[10px] tracking-wider">
                        <th class="p-3.5">Email & Name</th>
                        <th class="p-3.5">Source</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5">Subscribed Date</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($subscribers as $sub)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="p-3.5">
                            <span class="font-bold text-gray-900 block">{{ $sub->email }}</span>
                            @if($sub->name)
                                <span class="text-gray-500 text-[11px]">{{ $sub->name }}</span>
                            @endif
                        </td>
                        <td class="p-3.5">
                            <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $sub->source ?? 'web_footer' }}
                            </span>
                        </td>
                        <td class="p-3.5">
                            @if($sub->status === 'subscribed')
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                    Subscribed
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                    Unsubscribed
                                </span>
                            @endif
                        </td>
                        <td class="p-3.5 text-gray-500">
                            {{ $sub->subscribed_at ? $sub->subscribed_at->format('M d, Y') : $sub->created_at->format('M d, Y') }}
                        </td>
                        <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                            <form action="{{ route('admin.marketing.subscribers.toggle', $sub) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg {{ $sub->status === 'subscribed' ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                    {{ $sub->status === 'subscribed' ? 'Unsubscribe' : 'Resubscribe' }}
                                </button>
                            </form>

                            <form action="{{ route('admin.marketing.subscribers.destroy', $sub) }}" method="POST" class="inline" onsubmit="return confirm('Delete this subscriber permanently?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2 py-1 text-xs font-semibold text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200">
            {{ $subscribers->links() }}
        </div>
        @else
        <div class="p-10 text-center text-xs text-gray-400">
            No subscribers match the given criteria.
        </div>
        @endif
    </div>
</div>

<!-- Modal: Add Subscriber -->
<div id="addSubscriberModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 class="font-bold text-gray-900 text-sm">Add New Subscriber</h3>
            <button onclick="document.getElementById('addSubscriberModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('admin.marketing.subscribers.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" required placeholder="guest@example.com" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Subscriber Full Name (Optional)</label>
                <input type="text" name="name" placeholder="John Doe" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Lead Source Tag</label>
                <input type="text" name="source" value="manual_admin_entry" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('addSubscriberModal').classList.add('hidden')" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-primary rounded-xl hover:bg-primary/90 shadow">
                    Save Subscriber
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
