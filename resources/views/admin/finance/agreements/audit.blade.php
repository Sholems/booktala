@extends('layouts.admin')
@section('page_title', 'Agreement Coverage Audit')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
            <a href="{{ route('admin.agreements.index') }}" class="hover:text-primary">Agreements</a>
            <i class="fas fa-chevron-right text-xs"></i>
            <span>Coverage Audit</span>
        </div>
        <h2 class="text-2xl font-bold">Agreement Coverage Audit</h2>
        <p class="text-sm text-gray-500">Audit external units to identify missing management agreements before period closure.</p>
    </div>

    <form method="GET" class="flex items-center gap-2">
        <select name="site_id" class="border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
            <option value="">All Visible Sites</option>
            @foreach($sites as $site)
            <option value="{{ $site->id }}" @selected($selectedSiteId == $site->id)>{{ $site->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-dark transition">
            Filter
        </button>
        @if($selectedSiteId)
        <a href="{{ route('admin.agreements.audit') }}" class="text-sm text-slate-500 hover:text-slate-800 underline">
            Clear
        </a>
        @endif
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="text-xs text-slate-500 font-medium">Total External Units</div>
        <div class="text-2xl font-bold text-slate-900 mt-1">{{ $audit['total_external_units'] }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="text-xs text-emerald-600 font-medium">Fully Covered Units</div>
        <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $audit['covered_units_count'] }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="text-xs text-rose-600 font-medium">Uncovered Units Requiring Action</div>
        <div class="text-2xl font-bold text-rose-600 mt-1">{{ $audit['uncovered_units_count'] }}</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <h3 class="font-bold text-slate-800">Uncovered External Units</h3>
        <span class="text-xs text-slate-500">{{ count($audit['uncovered_units']) }} units identified</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Unit / Code</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Site</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Owner</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Uncovered Dates / Issues</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-700">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($audit['uncovered_units'] as $item)
                <tr class="hover:bg-rose-50/30">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-900">{{ $item['unit']->title }}</div>
                        <div class="text-xs font-mono text-slate-500">{{ $item['unit']->unit_code }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-700">
                        {{ $item['site']->name ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-3 text-slate-700">
                        {{ $item['owner']->name ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="space-y-1">
                            @foreach($item['reasons'] as $reason)
                            <div class="text-xs text-rose-700 font-medium flex items-center gap-1.5">
                                <i class="fas fa-exclamation-triangle text-rose-500"></i>
                                {{ $reason }}
                            </div>
                            @endforeach
                            @if(!empty($item['uncovered_dates']))
                            <div class="text-xs text-slate-600">
                                Uncovered dates: <span class="font-mono">{{ implode(', ', $item['uncovered_dates']) }}</span>
                            </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.agreements.create', ['unit_id' => $item['unit']->id]) }}" class="inline-flex items-center gap-1 text-primary hover:underline font-medium text-xs">
                            <i class="fas fa-plus-circle"></i>
                            Draft Agreement
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-emerald-600">
                        <i class="fas fa-check-circle text-2xl mb-2 block"></i>
                        All external units in this scope have active reviewed management agreements!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
