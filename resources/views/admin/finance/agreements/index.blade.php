@extends('layouts.admin')
@section('page_title', 'Management Agreements')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Management Agreements</h2>
        <p class="text-sm text-gray-500">Effective-dated management contracts for externally owned units.</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.agreements.audit') }}" class="inline-flex items-center gap-2 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium transition">
            <i class="fas fa-clipboard-check text-slate-500"></i>
            Coverage Audit
        </a>
        <a href="{{ route('admin.agreements.create') }}" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">
            <i class="fas fa-plus"></i>
            New Agreement
        </a>
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

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
    <div class="p-4 border-b border-slate-200 bg-slate-50/50">
        <form method="GET" class="flex flex-wrap gap-3 items-center">
            <div class="min-w-[200px]">
                <select name="unit_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">All Units</option>
                    @foreach($units as $unit)
                    <option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->title }} ({{ $unit->unit_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[180px]">
                <select name="owner_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">All Owners</option>
                    @foreach($owners as $owner)
                    <option value="{{ $owner->id }}" @selected(request('owner_id') == $owner->id)>{{ $owner->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[140px]">
                <select name="status" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">All Statuses</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="reviewed" @selected(request('status') === 'reviewed')>Reviewed</option>
                    <option value="retired" @selected(request('status') === 'retired')>Retired</option>
                </select>
            </div>
            <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-900 transition">
                Filter
            </button>
            @if(request()->anyFilled(['unit_id', 'owner_id', 'status']))
            <a href="{{ route('admin.agreements.index') }}" class="text-sm text-slate-500 hover:text-slate-800 underline">
                Clear
            </a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Unit / Property</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Owner</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Effective Period</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-700">Commission</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-700">Expenses Deducted</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-700">Status</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-700">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($agreements as $agreement)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-900">{{ $agreement->unit->title ?? 'N/A' }}</div>
                        <div class="text-xs text-slate-500">{{ $agreement->unit->unit_code ?? '' }} · {{ $agreement->unit->site->name ?? '' }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="font-medium text-slate-800">{{ $agreement->owner->name ?? 'N/A' }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-700">
                        {{ $agreement->effective_from->format('j M Y') }}
                        $\rightarrow$
                        {{ $agreement->effective_to ? $agreement->effective_to->format('j M Y') : 'Indefinite' }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-slate-900">
                        {{ number_format($agreement->commission_rate, 1) }}%
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($agreement->deduct_expenses)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Yes</span>
                        @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">No</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($agreement->status === 'reviewed')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Reviewed</span>
                        @elseif($agreement->status === 'draft')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Draft</span>
                        @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Retired</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="{{ route('admin.agreements.show', $agreement) }}" class="text-primary hover:underline font-medium">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                        No management agreements found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($agreements->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $agreements->links() }}
    </div>
    @endif
</div>
@endsection
