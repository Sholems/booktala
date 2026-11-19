@extends('layouts.admin')
@section('page_title', 'Preventative Facilities Maintenance')

@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Preventative Facilities Maintenance</h2>
        <p class="text-sm text-gray-500">Scheduled maintenance routines for Standby Generators, Split ACs, Inverters, Plumbing & Fumigation across sites.</p>
    </div>
    <div class="flex flex-wrap items-center gap-3" x-data>
        <a href="{{ route('admin.facilities.generator-logs') }}" class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition">
            <i class="fas fa-gas-pump"></i>
            <span>Generator Fuel & Hours</span>
        </a>
        <button @click="$dispatch('open-facility-modal')" type="button" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-light text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition">
            <i class="fas fa-plus"></i>
            <span>New Schedule</span>
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
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Active Routines</p>
        <p class="text-2xl font-black text-slate-800">{{ number_format($stats['total_active']) }}</p>
        <span class="text-xs text-slate-400">Recurring schedules tracked</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-semibold text-rose-600 uppercase tracking-wider mb-1">Overdue Services</p>
        <p class="text-2xl font-black {{ $stats['overdue'] > 0 ? 'text-rose-600' : 'text-slate-800' }}">{{ number_format($stats['overdue']) }}</p>
        <span class="text-xs text-slate-400">{{ $stats['overdue'] > 0 ? 'Immediate vendor dispatch required' : 'All equipment on schedule' }}</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider mb-1">Due in 7 Days</p>
        <p class="text-2xl font-black text-slate-800">{{ number_format($stats['due_soon']) }}</p>
        <span class="text-xs text-slate-400">Upcoming preventative checks</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Estimated Budget</p>
        <p class="text-2xl font-black text-primary">₦{{ number_format($stats['estimated_monthly']) }}</p>
        <span class="text-xs text-slate-400">Total planned routine costs</span>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-slate-200 p-4 mb-6 shadow-sm">
    <form method="GET" action="{{ route('admin.facilities.index') }}" class="flex flex-wrap items-center gap-3">
        <div class="w-48">
            <select name="equipment_type" onchange="this.form.submit()" class="w-full text-xs border border-slate-200 rounded-lg px-3 py-2 bg-slate-50">
                <option value="">All Equipment Types</option>
                @foreach($equipmentTypes as $key => $label)
                    <option value="{{ $key }}" {{ request('equipment_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-48">
            <select name="site_id" onchange="this.form.submit()" class="w-full text-xs border border-slate-200 rounded-lg px-3 py-2 bg-slate-50">
                <option value="">All Sites</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                @endforeach
            </select>
        </div>
        @if(request('equipment_type') || request('site_id'))
            <a href="{{ route('admin.facilities.index') }}" class="text-xs text-rose-600 hover:underline">Reset Filters</a>
        @endif
    </form>
</div>

<!-- Schedules Table -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase font-bold border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3">Equipment / Routine</th>
                    <th class="px-4 py-3">Site / Unit</th>
                    <th class="px-4 py-3">Frequency</th>
                    <th class="px-4 py-3">Last Serviced</th>
                    <th class="px-4 py-3">Next Due On</th>
                    <th class="px-4 py-3">Vendor / Staff</th>
                    <th class="px-4 py-3">Cost Est.</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($schedules as $schedule)
                @php
                    $isOverdue = $schedule->isOverdue();
                    $isDueSoon = $schedule->isDueSoon();
                @endphp
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-lg {{ $schedule->equipment_type === 'generator' ? 'bg-amber-100 text-amber-800' : ($schedule->equipment_type === 'ac' ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-700') }}">
                                <i class="fas {{ $schedule->equipment_type === 'generator' ? 'fa-bolt' : ($schedule->equipment_type === 'ac' ? 'fa-fan' : ($schedule->equipment_type === 'plumbing' ? 'fa-faucet' : ($schedule->equipment_type === 'fumigation' ? 'fa-bug' : 'fa-solar-panel'))) }} text-xs"></i>
                            </span>
                            <div>
                                <p class="font-bold text-slate-800">{{ $schedule->title }}</p>
                                <span class="text-xs text-slate-400">{{ \App\Models\FacilityMaintenanceSchedule::equipmentTypes()[$schedule->equipment_type] ?? $schedule->equipment_type }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs">
                        <p class="font-semibold text-slate-800">{{ $schedule->site?->name ?? 'All Sites' }}</p>
                        @if($schedule->property)
                            <span class="text-slate-400">Unit: {{ $schedule->property->title }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600 font-medium">
                        Every {{ $schedule->frequency_days }} days
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">
                        {{ $schedule->last_serviced_on ? $schedule->last_serviced_on->format('M j, Y') : 'Never recorded' }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 {{ $isOverdue ? 'bg-rose-100 text-rose-800' : ($isDueSoon ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">
                            <i class="fas {{ $isOverdue ? 'fa-exclamation-triangle' : 'fa-calendar-check' }}"></i>
                            {{ $schedule->next_due_on->format('M j, Y') }}
                            @if($isOverdue) (Overdue) @elseif($isDueSoon) (Due Soon) @endif
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        {{ $schedule->vendor_or_staff ?: 'In-house Facility Team' }}
                    </td>
                    <td class="px-4 py-3 font-semibold text-xs text-slate-800">
                        ₦{{ number_format($schedule->estimated_cost, 2) }}
                    </td>
                    <td class="px-4 py-3 text-right" x-data="{ openComplete: false }">
                        <button @click="openComplete = true" type="button" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 text-xs font-bold transition">
                            <i class="fas fa-check"></i>
                            <span>Mark Serviced</span>
                        </button>

                        <!-- Mark Serviced Modal -->
                        <div x-show="openComplete" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
                            <div class="bg-white rounded-2xl max-w-sm w-full p-5 text-left shadow-2xl" @click.away="openComplete = false">
                                <h4 class="font-bold text-slate-800 text-sm mb-1">Confirm Service: {{ $schedule->title }}</h4>
                                <p class="text-xs text-slate-500 mb-4">Advance maintenance routine by {{ $schedule->frequency_days }} days.</p>

                                <form action="{{ route('admin.facilities.complete', $schedule) }}" method="POST" class="space-y-3">
                                    @csrf
                                    @method('PATCH')
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Actual Cost (₦)</label>
                                        <input type="number" step="0.01" name="actual_cost" value="{{ $schedule->estimated_cost }}" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Service Notes / Oil Changed</label>
                                        <input type="text" name="service_notes" placeholder="e.g. Filters and 15W40 oil replaced" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-xs">
                                    </div>
                                    <div class="flex justify-end gap-2 pt-2">
                                        <button @click="openComplete = false" type="button" class="px-3 py-1.5 border border-slate-200 text-slate-600 rounded text-xs font-medium">Cancel</button>
                                        <button type="submit" class="px-4 py-1.5 bg-emerald-600 text-white rounded text-xs font-bold hover:bg-emerald-700">Confirm Completed</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                        <i class="fas fa-tools text-4xl mb-3 text-slate-300 block"></i>
                        No preventative maintenance schedules configured yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($schedules->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $schedules->links() }}
    </div>
    @endif
</div>

<!-- New Schedule Modal -->
<div x-data="{ open: false }"
     x-on:open-facility-modal.window="open = true"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl" @click.away="open = false">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">New Preventative Maintenance Routine</h3>
            <button @click="open = false" type="button" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('admin.facilities.store') }}" method="POST" class="space-y-4 mt-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Site *</label>
                    <select name="site_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-primary">
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Equipment Category *</label>
                    <select name="equipment_type" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-primary">
                        @foreach($equipmentTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Routine Title *</label>
                <input type="text" name="title" required placeholder="e.g. 100kVA Generator 250hr Oil & Filter Service" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-primary">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Frequency (Days) *</label>
                    <input type="number" name="frequency_days" value="30" min="1" max="365" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Next Due Date *</label>
                    <input type="date" name="next_due_on" value="{{ now()->addDays(30)->format('Y-m-d') }}" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Vendor / Technician</label>
                    <input type="text" name="vendor_or_staff" placeholder="e.g. Mikano Engineering / In-house" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Estimated Cost (₦)</label>
                    <input type="number" step="0.01" name="estimated_cost" value="0" min="0" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Maintenance Scope / Checklist</label>
                <textarea name="notes" rows="2" placeholder="Replace fuel filters, check coolant level, check belt tension..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                <button @click="open = false" type="button" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-primary hover:bg-primary-light text-white rounded-lg text-xs font-bold shadow">Save Routine</button>
            </div>
        </form>
    </div>
</div>
@endsection
