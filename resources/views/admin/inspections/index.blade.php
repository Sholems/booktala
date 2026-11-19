@extends('layouts.admin')
@section('page_title', 'Housekeeping Room Inspections')

@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Housekeeping Room Inspections</h2>
        <p class="text-sm text-gray-500">Supervisors review and verify turnover cleaning checklists and before/after photos before rooms switch to Available.</p>
    </div>
    <div class="flex flex-wrap items-center gap-3" x-data>
        <button @click="$dispatch('open-inspection-modal')" type="button" class="inline-flex items-center gap-2 bg-[#0B2D5B] hover:bg-[#123E7A] text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition">
            <i class="fas fa-plus"></i>
            <span>Log Inspection</span>
        </button>
    </div>
</div>

@if(session('success'))
<div class="mb-6 bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-800 flex items-center gap-2">
    <i class="fas fa-check-circle text-emerald-600"></i>
    <span>{{ session('success') }}</span>
</div>
@endif

@if(session('error'))
<div class="mb-6 bg-rose-50 border border-rose-200 rounded-lg p-4 text-sm text-rose-800 flex items-center gap-2">
    <i class="fas fa-exclamation-circle text-rose-600"></i>
    <span>{{ session('error') }}</span>
</div>
@endif

<!-- Stats Overview -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider mb-1">Awaiting Supervisor Review</p>
        <p class="text-2xl font-black text-slate-800">{{ number_format($stats['pending_review']) }}</p>
        <span class="text-xs text-slate-500">Rooms currently held in Cleaning</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mb-1">Approved & Available Today</p>
        <p class="text-2xl font-black text-slate-800">{{ number_format($stats['approved_today']) }}</p>
        <span class="text-xs text-emerald-600 font-medium">Ready for guest check-in</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-semibold text-rose-600 uppercase tracking-wider mb-1">Rejected (Needs Rework)</p>
        <p class="text-2xl font-black text-slate-800">{{ number_format($stats['rejected']) }}</p>
        <span class="text-xs text-slate-400">Returned to cleaners for rework</span>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-slate-200 p-4 mb-6 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.inspections.index', ['status' => 'pending_review']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'pending_review' ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Pending Review ({{ $stats['pending_review'] }})
            </a>
            <a href="{{ route('admin.inspections.index', ['status' => 'approved']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'approved' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Approved
            </a>
            <a href="{{ route('admin.inspections.index', ['status' => 'rejected']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'rejected' ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Rejected
            </a>
            <a href="{{ route('admin.inspections.index', ['status' => 'all']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'all' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                All Inspections
            </a>
        </div>
        <form method="GET" action="{{ route('admin.inspections.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <select name="unit_id" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-lg px-3 py-1.5 bg-slate-50 focus:ring-2 focus:ring-[#0B2D5B]">
                <option value="">All Units / Rooms</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}" {{ request('unit_id') == $unit->id ? 'selected' : '' }}>
                        {{ $unit->title }} ({{ $unit->unit_code }})
                    </option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<!-- Inspections Table -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase font-bold border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3">Unit / Property</th>
                    <th class="px-4 py-3">Cleaner</th>
                    <th class="px-4 py-3">Submitted</th>
                    <th class="px-4 py-3">Photos</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Supervisor</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($inspections as $inspection)
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="px-4 py-3 font-semibold text-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full {{ $inspection->unit?->status === 'available' ? 'bg-emerald-500' : ($inspection->unit?->status === 'cleaning' ? 'bg-purple-500' : 'bg-blue-500') }}"></span>
                            <div>
                                <p class="font-bold text-gray-900">{{ $inspection->unit?->title ?? 'N/A' }}</p>
                                <span class="text-xs text-slate-400">{{ $inspection->unit?->site?->name ?? 'Site' }} &bull; Current Unit Status: <strong class="uppercase">{{ $inspection->unit?->status ?? 'cleaning' }}</strong></span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-600">
                        <span class="font-medium text-slate-800">{{ $inspection->cleaner?->name ?? 'Unassigned' }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">
                        {{ $inspection->submitted_at ? $inspection->submitted_at->format('M j, Y g:i A') : 'N/A' }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-semibold">
                                <i class="fas fa-camera mr-1"></i> {{ count($inspection->before_photos ?? []) }} Before
                            </span>
                            <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-semibold">
                                <i class="fas fa-camera mr-1"></i> {{ count($inspection->after_photos ?? []) }} After
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @if($inspection->status === 'approved')
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 inline-flex items-center gap-1">
                                <i class="fas fa-check-circle"></i> Approved
                            </span>
                        @elseif($inspection->status === 'rejected')
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 inline-flex items-center gap-1">
                                <i class="fas fa-times-circle"></i> Rejected
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 inline-flex items-center gap-1">
                                <i class="fas fa-clock"></i> Pending Review
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">
                        {{ $inspection->supervisor?->name ?? 'Awaiting' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.inspections.show', $inspection) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-[#0B2D5B] hover:text-white text-slate-700 text-xs font-bold transition">
                            <span>Review & Verify</span>
                            <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-slate-400">
                        <i class="fas fa-clipboard-check text-4xl mb-3 text-slate-300 block"></i>
                        No room inspections found matching this filter.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($inspections->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $inspections->links() }}
    </div>
    @endif
</div>

<!-- Log Inspection Modal -->
<div x-data="{ open: false }"
     x-on:open-inspection-modal.window="open = true"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl" @click.away="open = false">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">Submit Housekeeping Inspection</h3>
            <button @click="open = false" type="button" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('admin.inspections.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Unit / Room *</label>
                <select name="unit_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0B2D5B]">
                    <option value="">Select unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->title }} ({{ $unit->unit_code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Cleaner Assigned *</label>
                <select name="cleaner_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0B2D5B]">
                    <option value="{{ auth()->id() }}">{{ auth()->user()->name }} (Current User)</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Before Photos</label>
                    <input type="file" name="before_images[]" multiple accept="image/*" class="w-full text-xs text-slate-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">After Photos</label>
                    <input type="file" name="after_images[]" multiple accept="image/*" class="w-full text-xs text-slate-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Cleaner Turnover Notes</label>
                <textarea name="cleaner_notes" rows="2" placeholder="Linen refreshed, bathroom sanitized, AC remote replaced..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0B2D5B]"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                <button @click="open = false" type="button" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-semibold hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-[#0B2D5B] hover:bg-[#123E7A] text-white rounded-lg text-sm font-bold shadow">Submit For Review</button>
            </div>
        </form>
    </div>
</div>
@endsection
