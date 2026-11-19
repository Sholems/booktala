@extends('layouts.staff')
@section('title', 'Submit Cleaning Inspection')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('staff.tasks.show', $task) }}" class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition">
            <i class="fas fa-arrow-left text-xs"></i>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-800">Turnover Inspection Checklist</h2>
            <p class="text-xs text-slate-500">{{ $task->property?->title ?? 'Room' }} &bull; Task #{{ $task->id }}</p>
        </div>
    </div>

    <form action="{{ route('staff.inspections.store', $task) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Before & After Photos -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-camera text-[#0B2D5B]"></i>
                Photo Verification (Before & After)
            </h3>
            <p class="text-xs text-slate-500">Take clear photos of the room before cleaning and after completion.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                        <span class="w-2 h-2 rounded-full bg-rose-500 inline-block mr-1"></span> Before Photos
                    </label>
                    <input type="file" name="before_images[]" multiple accept="image/*" class="w-full text-xs text-slate-500 mb-2">
                    <textarea name="before_urls" rows="2" placeholder="Or paste image URLs (one per line)..." class="w-full px-2.5 py-1.5 border border-slate-200 rounded-lg text-xs bg-white"></textarea>
                </div>

                <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block mr-1"></span> After Photos
                    </label>
                    <input type="file" name="after_images[]" multiple accept="image/*" class="w-full text-xs text-slate-500 mb-2">
                    <textarea name="after_urls" rows="2" placeholder="Or paste image URLs (one per line)..." class="w-full px-2.5 py-1.5 border border-slate-200 rounded-lg text-xs bg-white"></textarea>
                </div>
            </div>
        </div>

        <!-- Checklist -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-5">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-tasks text-emerald-600"></i>
                Turnover Cleaning Checklist
            </h3>

            @foreach($checklist as $gIndex => $group)
            <div class="border border-slate-100 rounded-xl p-4 bg-slate-50/60">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                    {{ $group['section'] }}
                </h4>
                <input type="hidden" name="checklist[{{ $gIndex }}][section]" value="{{ $group['section'] }}">
                <div class="space-y-2">
                    @foreach($group['items'] as $iIndex => $item)
                    <label class="flex items-start gap-2.5 p-2 bg-white rounded-lg border border-slate-100 cursor-pointer hover:bg-slate-50 transition">
                        <input type="checkbox" name="checklist[{{ $gIndex }}][items][{{ $iIndex }}][passed]" value="1" checked
                               class="rounded text-[#0B2D5B] focus:ring-[#0B2D5B] mt-0.5">
                        <input type="hidden" name="checklist[{{ $gIndex }}][items][{{ $iIndex }}][key]" value="{{ $item['key'] }}">
                        <input type="hidden" name="checklist[{{ $gIndex }}][items][{{ $iIndex }}][label]" value="{{ $item['label'] }}">
                        <span class="text-xs text-slate-800 font-medium">{{ $item['label'] }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <!-- Cleaner Notes -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Turnover Notes for Supervisor</label>
            <textarea name="cleaner_notes" rows="2" placeholder="e.g. Extra pillows placed in wardrobe, shampoo dispenser refilled..."
                class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-[#0B2D5B]"></textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('staff.tasks.show', $task) }}" class="px-5 py-2.5 border border-slate-200 text-slate-600 font-bold text-xs rounded-lg hover:bg-slate-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-[#0B2D5B] hover:bg-[#123E7A] text-white font-bold text-xs rounded-lg transition shadow flex items-center gap-2">
                <i class="fas fa-check"></i>
                <span>Submit for Supervisor Inspection</span>
            </button>
        </div>
    </form>
</div>
@endsection
