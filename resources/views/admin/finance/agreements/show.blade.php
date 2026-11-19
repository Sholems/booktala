@extends('layouts.admin')
@section('page_title', 'Management Agreement Details')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
        <a href="{{ route('admin.agreements.index') }}" class="hover:text-primary">Agreements</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span>Agreement #{{ $agreement->id }}</span>
    </div>
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold flex items-center gap-3">
                Agreement #{{ $agreement->id }}
                @if($agreement->status === 'reviewed')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Reviewed / Active</span>
                @elseif($agreement->status === 'draft')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Draft</span>
                @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Retired</span>
                @endif
            </h2>
            <p class="text-sm text-gray-500">{{ $agreement->unit->title ?? 'N/A' }} · {{ $agreement->owner->name ?? 'N/A' }}</p>
        </div>

        <div class="flex items-center gap-2">
            @if($agreement->status === 'draft')
            <form action="{{ route('admin.agreements.review', $agreement) }}" method="POST" onsubmit="return confirm('Review and activate this agreement? Once reviewed, commercial terms become immutable.');">
                @csrf
                @method('PATCH')
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition inline-flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    Review & Activate
                </button>
            </form>
            <form action="{{ route('admin.agreements.destroy', $agreement) }}" method="POST" onsubmit="return confirm('Delete this draft agreement?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="border border-rose-300 text-rose-700 hover:bg-rose-50 px-4 py-2 rounded-lg text-sm font-medium transition inline-flex items-center gap-2">
                    <i class="fas fa-trash"></i>
                    Delete Draft
                </button>
            </form>
            @elseif($agreement->status === 'reviewed')
            <form action="{{ route('admin.agreements.retire', $agreement) }}" method="POST" onsubmit="return confirm('Retire this agreement? It will no longer apply to future economic periods.');">
                @csrf
                @method('PATCH')
                <button type="submit" class="border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium transition inline-flex items-center gap-2">
                    <i class="fas fa-archive"></i>
                    Retire Agreement
                </button>
            </form>
            @endif
        </div>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Unit & Ownership</h3>
        <div>
            <div class="text-xs text-slate-400">Unit / Property</div>
            <div class="font-semibold text-slate-900">{{ $agreement->unit->title ?? 'N/A' }}</div>
            <div class="text-xs text-slate-500">{{ $agreement->unit->unit_code ?? '' }} · {{ $agreement->unit->site->name ?? '' }}</div>
        </div>
        <div>
            <div class="text-xs text-slate-400">Owner</div>
            <div class="font-semibold text-slate-900">{{ $agreement->owner->name ?? 'N/A' }}</div>
            <div class="text-xs text-slate-500">{{ $agreement->owner->email ?? 'No email' }} · {{ $agreement->owner->phone ?? 'No phone' }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Commercial Terms</h3>
        <div>
            <div class="text-xs text-slate-400">Commission Rate</div>
            <div class="text-2xl font-bold text-slate-900">{{ number_format($agreement->commission_rate, 2) }}%</div>
        </div>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div>
                <span class="text-xs text-slate-400 block">Deduct Expenses</span>
                <span class="font-medium text-slate-800">{{ $agreement->deduct_expenses ? 'Yes' : 'No' }}</span>
            </div>
            <div>
                <span class="text-xs text-slate-400 block">Caution Recipient</span>
                <span class="font-medium text-slate-800 capitalize">{{ $agreement->caution_forfeiture_recipient }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Lifecycle & Dates</h3>
        <div>
            <div class="text-xs text-slate-400">Effective Period</div>
            <div class="font-medium text-slate-900">
                {{ $agreement->effective_from->format('j M Y') }}
                $\rightarrow$
                {{ $agreement->effective_to ? $agreement->effective_to->format('j M Y') : 'Indefinite' }}
            </div>
        </div>
        <div class="text-xs text-slate-500 space-y-1">
            <div>Drafted by: <span class="text-slate-800 font-medium">{{ $agreement->creator->name ?? 'System' }}</span> ({{ $agreement->created_at->format('j M Y, H:i') }})</div>
            @if($agreement->reviewed_at)
            <div>Reviewed by: <span class="text-slate-800 font-medium">{{ $agreement->reviewer->name ?? 'Unknown' }}</span> ({{ $agreement->reviewed_at->format('j M Y, H:i') }})</div>
            @endif
        </div>
    </div>
</div>

@if($agreement->notes)
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-2">Contract Notes</h3>
    <p class="text-sm text-slate-700 whitespace-pre-line">{{ $agreement->notes }}</p>
</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <h3 class="font-bold text-slate-800">Associated Journal Entries</h3>
        <span class="text-xs text-slate-500">{{ $agreement->financeEntries->count() }} recent entries</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Date</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Account</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Reservation</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-700">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($agreement->financeEntries as $entry)
                <tr>
                    <td class="px-4 py-3 text-slate-600">{{ $entry->occurred_on->format('j M Y') }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-800">{{ $entry->account }}</td>
                    <td class="px-4 py-3">
                        @if($entry->reservation_id)
                        <a href="{{ route('admin.reservations.show', $entry->reservation_id) }}" class="text-primary hover:underline">
                            Booking #{{ $entry->reservation_id }}
                        </a>
                        @else
                        <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-semibold font-mono {{ $entry->amount_minor < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                        ₦{{ number_format($entry->amount_minor / 100, 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-slate-400">
                        No financial postings have been captured under this agreement yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
