@extends('layouts.admin')
@section('page_title', 'Dynamic Rate Simulator')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Dynamic Rate Simulator</h2>
        <p class="text-sm text-gray-500">Preview calculated nightly prices across seasonal rules, weekend premiums & holiday surges.</p>
    </div>
    <div>
        <a href="{{ route('admin.pricing-rules.index') }}" class="inline-flex items-center gap-2 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">
            <i class="fas fa-arrow-left text-slate-500"></i>
            <span>Back to Rules</span>
        </a>
    </div>
</div>

<!-- Simulator Control Bar -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-end justify-between gap-4">
        <div class="flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Select Property / Unit</label>
                <select name="unit_id" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent min-w-[240px]" onchange="this.form.submit()">
                    @foreach($units as $u)
                    <option value="{{ $u->id }}" @selected($selectedUnit && $selectedUnit->id == $u->id)>{{ $u->title }} ({{ $u->unit_code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Select Month</label>
                <input type="month" name="month" value="{{ $monthStr }}" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent" onchange="this.form.submit()">
            </div>
        </div>

        @if($selectedUnit)
        <div class="text-xs text-slate-500 bg-slate-50 px-3 py-2 rounded-lg border border-slate-200">
            Standard Base Rate: <span class="font-bold text-slate-800">₦{{ number_format($selectedUnit->price_per_night) }}</span> / night
            @if($selectedUnit->weekend_price)
            • Weekend Rate: <span class="font-bold text-amber-700">₦{{ number_format($selectedUnit->weekend_price) }}</span>
            @endif
        </div>
        @endif
    </form>
</div>

<!-- Calendar Grid -->
@if(!empty($calendar))
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden p-6 mb-6">
    <h3 class="text-base font-bold text-slate-800 mb-4 flex items-center justify-between">
        <span>Calendar for {{ \Carbon\Carbon::parse($monthStr.'-01')->format('F Y') }}</span>
        <span class="text-xs font-normal text-slate-400">{{ count($calendar) }} days evaluated</span>
    </h3>

    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2.5">
        @foreach($calendar as $day)
        @php
            $isOverridden = !empty($day['rule_name']);
            $isHigher = $day['price'] > $selectedUnit->price_per_night;
            $isLower = $day['price'] < $selectedUnit->price_per_night;
        @endphp
        <div class="rounded-xl border p-3 flex flex-col justify-between min-h-[95px] transition {{ $day['is_weekend'] ? 'bg-amber-50/30 border-amber-200' : 'bg-slate-50/50 border-slate-200' }} {{ $isOverridden ? 'ring-1 ring-primary/40' : '' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold {{ $day['is_weekend'] ? 'text-amber-800' : 'text-slate-700' }}">{{ \Carbon\Carbon::parse($day['date'])->format('j') }}</span>
                <span class="text-[10px] font-semibold text-slate-400 uppercase">{{ $day['day_name'] }}</span>
            </div>

            <div class="my-1.5">
                <div class="text-sm font-bold {{ $isHigher ? 'text-rose-600' : ($isLower ? 'text-emerald-600' : 'text-slate-800') }}">
                    ₦{{ number_format($day['price']) }}
                </div>
            </div>

            <div>
                @if($day['rule_name'])
                <span class="inline-block text-[9px] px-1.5 py-0.5 rounded font-semibold bg-primary/10 text-primary truncate max-w-full" title="{{ $day['rule_name'] }}">
                    {{ $day['rule_name'] }}
                </span>
                @else
                <span class="text-[9px] text-slate-400">Standard</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
