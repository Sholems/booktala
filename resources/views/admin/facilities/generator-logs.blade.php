@extends('layouts.admin')
@section('page_title', 'Generator Run-Hours & Diesel Fuel Logs')

@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <a href="{{ route('admin.facilities.index') }}" class="text-xs text-slate-500 hover:text-slate-800">
                <i class="fas fa-arrow-left mr-1"></i> Facilities Overview
            </a>
        </div>
        <h2 class="text-2xl font-bold">Generator Fuel & Run-Hour Tracker</h2>
        <p class="text-sm text-gray-500">Monitor standby generator run hours, diesel fuel replenishment, and fuel burn efficiency across properties.</p>
    </div>
    <div class="flex flex-wrap items-center gap-3" x-data>
        <button @click="$dispatch('open-genlog-modal')" type="button" class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition">
            <i class="fas fa-plus"></i>
            <span>Log Run-Hours / Diesel</span>
        </button>
    </div>
</div>

@if(session('success'))
<div class="mb-6 bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-800 flex items-center gap-2">
    <i class="fas fa-check-circle text-emerald-600"></i>
    <span>{{ session('success') }}</span>
</div>
@endif

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Run Hours</p>
        <p class="text-2xl font-black text-slate-800">{{ number_format($stats['total_run_hours'], 1) }} hrs</p>
        <span class="text-xs text-slate-400">Recorded standby generator runtime</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider mb-1">Diesel Added</p>
        <p class="text-2xl font-black text-amber-700">{{ number_format($stats['total_diesel_litres'], 1) }} L</p>
        <span class="text-xs text-slate-400">Total litres supplied</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Fuel Spend</p>
        <p class="text-2xl font-black text-primary">₦{{ number_format($stats['total_diesel_cost'], 2) }}</p>
        <span class="text-xs text-slate-400">Diesel replenishment expenditure</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mb-1">Burn Efficiency</p>
        <p class="text-2xl font-black text-slate-800">{{ $stats['avg_consumption'] }} L/hr</p>
        <span class="text-xs text-emerald-600 font-medium">Average fuel burn rate</span>
    </div>
</div>

<!-- Logs Table -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase font-bold border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3">Site / Generator</th>
                    <th class="px-4 py-3">Logged Date</th>
                    <th class="px-4 py-3">Meter (Start &rarr; End)</th>
                    <th class="px-4 py-3">Runtime</th>
                    <th class="px-4 py-3">Diesel Added</th>
                    <th class="px-4 py-3">Fuel Cost</th>
                    <th class="px-4 py-3">Fuel Level</th>
                    <th class="px-4 py-3">Logged By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="px-4 py-3 font-semibold text-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center text-xs">
                                <i class="fas fa-bolt"></i>
                            </span>
                            <div>
                                <p class="font-bold text-gray-900">{{ $log->generator_name }}</p>
                                <span class="text-xs text-slate-400">{{ $log->site?->name ?? 'Site' }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">
                        {{ $log->logged_at ? $log->logged_at->format('M j, Y g:i A') : 'N/A' }}
                    </td>
                    <td class="px-4 py-3 text-xs font-mono text-slate-700">
                        {{ number_format($log->run_hours_start, 1) }} &rarr; {{ number_format($log->run_hours_end, 1) }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-800">
                            {{ number_format($log->hours_run, 1) }} hrs
                        </span>
                    </td>
                    <td class="px-4 py-3 font-bold text-xs text-amber-700">
                        {{ number_format($log->litres_added, 1) }} L
                    </td>
                    <td class="px-4 py-3 font-black text-xs text-slate-800">
                        ₦{{ number_format($log->diesel_cost, 2) }}
                    </td>
                    <td class="px-4 py-3 text-xs">
                        @if($log->fuel_level_after_percent !== null)
                            <span class="font-semibold text-slate-700">{{ $log->fuel_level_after_percent }}%</span>
                            <span class="text-[10px] text-slate-400 block">(was {{ $log->fuel_level_before_percent ?? '?' }}%)</span>
                        @else
                            <span class="text-slate-400">N/A</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        {{ $log->loggedBy?->name ?? 'Staff' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                        <i class="fas fa-gas-pump text-4xl mb-3 text-slate-300 block"></i>
                        No generator run-hour or diesel replenishment logs found.
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
</div>

<!-- Log Generator Modal -->
<div x-data="{ open: false }"
     x-on:open-genlog-modal.window="open = true"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl" @click.away="open = false">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">Log Generator Run-Hours & Diesel</h3>
            <button @click="open = false" type="button" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('admin.facilities.generator-logs.store') }}" method="POST" class="space-y-4 mt-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Site *</label>
                    <select name="site_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-amber-500">
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Generator Name *</label>
                    <input type="text" name="generator_name" value="100kVA Perkins Standby Gen" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Run Hours Start *</label>
                    <input type="number" step="0.1" name="run_hours_start" value="1240.0" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Run Hours End *</label>
                    <input type="number" step="0.1" name="run_hours_end" value="1248.5" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Diesel Added (Litres) *</label>
                    <input type="number" step="0.1" name="litres_added" value="150" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Diesel Cost (₦) *</label>
                    <input type="number" step="0.01" name="diesel_cost" value="187500" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Fuel Level Before (%)</label>
                    <input type="number" name="fuel_level_before_percent" min="0" max="100" placeholder="e.g. 25" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Fuel Level After (%)</label>
                    <input type="number" name="fuel_level_after_percent" min="0" max="100" placeholder="e.g. 95" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Log Date & Time *</label>
                <input type="datetime-local" name="logged_at" value="{{ now()->format('Y-m-d\TH:i') }}" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Notes / Supplier</label>
                <input type="text" name="notes" placeholder="e.g. TotalEnergies diesel supply receipt #4491" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                <button @click="open = false" type="button" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold shadow">Save Log Entry</button>
            </div>
        </form>
    </div>
</div>
@endsection
