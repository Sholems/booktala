@extends('layouts.admin')
@section('page_title', 'Review Housekeeping Inspection #' . $inspection->id)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.inspections.index') }}" class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition">
                    <i class="fas fa-arrow-left text-xs"></i>
                </a>
                <h2 class="text-2xl font-bold text-slate-800">Room Inspection #{{ $inspection->id }}</h2>
                @if($inspection->status === 'approved')
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 inline-flex items-center gap-1">
                        <i class="fas fa-check-circle"></i> Approved
                    </span>
                @elseif($inspection->status === 'rejected')
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 inline-flex items-center gap-1">
                        <i class="fas fa-times-circle"></i> Rejected
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 inline-flex items-center gap-1">
                        <i class="fas fa-clock"></i> Pending Supervisor Review
                    </span>
                @endif
            </div>
            <p class="text-slate-500 text-xs mt-1 ml-9">
                {{ $inspection->unit?->title }} ({{ $inspection->unit?->unit_code }}) &bull; {{ $inspection->unit?->site?->name }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500 font-medium">Current Room Status:</span>
            <span class="px-3 py-1 rounded-lg text-xs font-black uppercase {{ $inspection->unit?->status === 'available' ? 'bg-emerald-100 text-emerald-800' : ($inspection->unit?->status === 'cleaning' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800') }}">
                {{ $inspection->unit?->status ?? 'cleaning' }}
            </span>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-800 flex items-center gap-2">
        <i class="fas fa-check-circle text-emerald-600"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-rose-50 border border-rose-200 rounded-lg p-4 text-sm text-rose-800 flex items-center gap-2">
        <i class="fas fa-exclamation-circle text-rose-600"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <!-- Metadata Card -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-400 uppercase font-semibold block mb-0.5">Cleaner</span>
                <span class="font-bold text-slate-800 text-sm">{{ $inspection->cleaner?->name ?? 'Unassigned' }}</span>
            </div>
            <div>
                <span class="text-slate-400 uppercase font-semibold block mb-0.5">Submitted At</span>
                <span class="font-bold text-slate-800 text-sm">{{ $inspection->submitted_at ? $inspection->submitted_at->format('M j, Y g:i A') : 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-400 uppercase font-semibold block mb-0.5">Supervisor</span>
                <span class="font-bold text-slate-800 text-sm">{{ $inspection->supervisor?->name ?? 'Pending' }}</span>
            </div>
            <div>
                <span class="text-slate-400 uppercase font-semibold block mb-0.5">Reviewed At</span>
                <span class="font-bold text-slate-800 text-sm">{{ $inspection->reviewed_at ? $inspection->reviewed_at->format('M j, Y g:i A') : 'Awaiting' }}</span>
            </div>
        </div>

        @if($inspection->cleaner_notes)
        <div class="mt-4 pt-4 border-t border-slate-100 text-xs">
            <span class="text-slate-500 font-bold block mb-1">Cleaner Turnover Notes:</span>
            <p class="text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100">{{ $inspection->cleaner_notes }}</p>
        </div>
        @endif

        @if($inspection->supervisor_notes)
        <div class="mt-3 text-xs">
            <span class="text-slate-500 font-bold block mb-1">Supervisor Review Feedback:</span>
            <p class="text-slate-700 bg-amber-50 p-3 rounded-lg border border-amber-100">{{ $inspection->supervisor_notes }}</p>
        </div>
        @endif
    </div>

    <!-- Before & After Photos Gallery -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h3 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="fas fa-images text-[#0B2D5B]"></i>
            Turnover Photo Verification
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Before Photos -->
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <span class="font-bold text-xs uppercase text-slate-600 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Before Cleaning
                    </span>
                    <span class="text-xs text-slate-400">{{ count($inspection->before_photos ?? []) }} photo(s)</span>
                </div>
                
                @if(empty($inspection->before_photos))
                    <div class="bg-slate-50 border border-dashed border-slate-200 rounded-xl p-8 text-center text-xs text-slate-400">
                        <i class="fas fa-camera text-2xl mb-1 text-slate-300 block"></i>
                        No before-photos uploaded.
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($inspection->before_photos as $photo)
                        <a href="{{ $photo }}" target="_blank" class="block aspect-video bg-slate-100 rounded-lg overflow-hidden border border-slate-200 hover:opacity-90 transition relative group">
                            <img src="{{ $photo }}" alt="Before Cleaning" class="w-full h-full object-cover">
                            <span class="absolute bottom-1 right-1 bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition">Zoom</span>
                        </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- After Photos -->
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <span class="font-bold text-xs uppercase text-slate-600 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> After Cleaning & Readiness
                    </span>
                    <span class="text-xs text-slate-400">{{ count($inspection->after_photos ?? []) }} photo(s)</span>
                </div>
                
                @if(empty($inspection->after_photos))
                    <div class="bg-slate-50 border border-dashed border-slate-200 rounded-xl p-8 text-center text-xs text-slate-400">
                        <i class="fas fa-camera text-2xl mb-1 text-slate-300 block"></i>
                        No after-photos uploaded.
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($inspection->after_photos as $photo)
                        <a href="{{ $photo }}" target="_blank" class="block aspect-video bg-slate-100 rounded-lg overflow-hidden border border-slate-200 hover:opacity-90 transition relative group">
                            <img src="{{ $photo }}" alt="After Cleaning" class="w-full h-full object-cover">
                            <span class="absolute bottom-1 right-1 bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition">Zoom</span>
                        </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Checklist Sections -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h3 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="fas fa-tasks text-emerald-600"></i>
            Inspection Checklist Items
        </h3>

        @php
            $checklistData = $inspection->checklist ?: \App\Models\HousekeepingInspection::defaultChecklist();
        @endphp

        <div class="space-y-5">
            @foreach($checklistData as $group)
            <div class="border border-slate-100 rounded-xl p-4 bg-slate-50/50">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-check-square text-emerald-600"></i>
                    {{ $group['section'] ?? 'Checklist Section' }}
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                    @foreach($group['items'] ?? [] as $item)
                    <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-slate-100">
                        <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] {{ ($item['passed'] ?? true) ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                            <i class="fas {{ ($item['passed'] ?? true) ? 'fa-check' : 'fa-times' }}"></i>
                        </span>
                        <span class="text-slate-800 font-medium">{{ $item['label'] ?? $item['key'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Supervisor Review Actions -->
    @if($inspection->status === 'pending_review' || auth()->user()->hasRole('super_admin'))
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Approve Card -->
        <div class="bg-white rounded-xl border-2 border-emerald-200 p-5 shadow-sm">
            <div class="flex items-center gap-2 mb-2 text-emerald-700">
                <i class="fas fa-check-circle text-lg"></i>
                <h4 class="font-bold text-sm">Approve Inspection</h4>
            </div>
            <p class="text-xs text-slate-500 mb-4">
                Marks cleaning complete and switches room status from <strong>Cleaning</strong> to <strong>Available</strong> for immediate guest arrival.
            </p>

            <form action="{{ route('admin.inspections.approve', $inspection) }}" method="POST" class="space-y-3">
                @csrf
                @method('PATCH')
                <input type="text" name="notes" placeholder="Optional approval notes (e.g. Excellent work)..."
                    class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-emerald-500">
                <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition shadow flex items-center justify-center gap-2">
                    <i class="fas fa-check"></i>
                    <span>Approve & Set Room Available</span>
                </button>
            </form>
        </div>

        <!-- Reject Card -->
        <div class="bg-white rounded-xl border-2 border-rose-200 p-5 shadow-sm">
            <div class="flex items-center gap-2 mb-2 text-rose-700">
                <i class="fas fa-times-circle text-lg"></i>
                <h4 class="font-bold text-sm">Reject & Request Rework</h4>
            </div>
            <p class="text-xs text-slate-500 mb-4">
                Keeps room in <strong>Cleaning</strong> status and dispatches feedback to the cleaner for immediate rework.
            </p>

            <form action="{{ route('admin.inspections.reject', $inspection) }}" method="POST" class="space-y-3">
                @csrf
                @method('PATCH')
                <input type="text" name="notes" required placeholder="Required: Explain what needs rework (e.g. Balcony floor dusty)..."
                    class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-rose-500">
                <button type="submit" class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs transition shadow flex items-center justify-center gap-2">
                    <i class="fas fa-undo"></i>
                    <span>Reject & Send Back for Rework</span>
                </button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
