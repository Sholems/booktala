@extends('layouts.staff')
@section('page_title', 'Team Overview')
@section('content')
@php
    $shiftState = function ($shift) use ($missedGrace) {
        $a = $shift->attendance;
        if ($a && $a->clock_out_at) return ['Done', 'bg-gray-100 text-gray-700'];
        if ($a) return [$a->isLate() ? 'On duty · ' . $a->late_minutes . ' min late' : 'On duty', $a->isLate() ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800'];
        if (now()->lt($shift->startsAt())) return ['Starts ' . $shift->startsAt()->format('g:i A'), 'bg-blue-50 text-blue-700'];
        if (now()->lt($shift->startsAt()->addMinutes($missedGrace))) return ['Not in yet', 'bg-yellow-100 text-yellow-800'];
        return ['No clock-in', 'bg-red-100 text-red-800'];
    };
    $missing = $shifts->filter(fn ($s) => !$s->attendance && now()->gte($s->startsAt()->addMinutes($missedGrace)) && now()->lt($s->endsAt()))->count();
    $unassigned = $tasks->whereNull('assigned_to')->count() + $repairs->whereNull('assigned_to')->count();
    $escalated = $tasks->where('escalation_level', '>', 0)->count() + $repairs->where('escalation_level', '>', 0)->count();
    $tiles = [
        ['On duty now', $onDuty->count(), 'fa-user-check', 'text-green-600'],
        ['No clock-in', $missing, 'fa-user-times', 'text-red-600'],
        ['Unassigned work', $unassigned, 'fa-inbox', 'text-yellow-600'],
        ['Overdue (escalated)', $escalated, 'fa-exclamation-triangle', 'text-orange-600'],
        ['Arrivals today', $arrivals, 'fa-plane-arrival', 'text-blue-600'],
        ['Departures due', $departures, 'fa-door-open', 'text-indigo-600'],
    ];
@endphp

<div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h2 class="text-xl font-bold">Today at {{ $selectedSite ? $sites->firstWhere('id', $selectedSite)->name : ($sites->count() === 1 ? $sites->first()->name : 'your sites') }}</h2>
        <p class="text-sm text-gray-500">{{ now()->format('l, j F Y · g:i A') }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('staff.team.roster', array_filter(['site' => $selectedSite])) }}" class="px-4 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary"><i class="fas fa-calendar-week mr-1"></i> Roster</a>
        <a href="{{ route('staff.team.attendance', array_filter(['site' => $selectedSite])) }}" class="px-4 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary"><i class="fas fa-user-clock mr-1"></i> Attendance</a>
    </div>
</div>

@include('staff.team._site-filter')

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
    @foreach($tiles as [$label, $count, $icon, $color])
    <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-100 {{ $count === 0 ? 'opacity-60' : '' }}">
        <div class="flex items-center justify-between">
            <span class="text-2xl font-bold {{ $count ? $color : 'text-gray-400' }}">{{ $count }}</span>
            <i class="fas {{ $icon }} {{ $count ? $color : 'text-gray-300' }}"></i>
        </div>
        <div class="mt-1 text-xs text-gray-600">{{ $label }}</div>
    </div>
    @endforeach
</div>

@if($errors->has('assigned_to'))
<div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">{{ $errors->first('assigned_to') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow">
        <div class="px-5 py-3 border-b font-semibold">Today's shifts</div>
        <ul class="divide-y">
            @forelse($shifts as $shift)
            @php [$state, $badge] = $shiftState($shift); @endphp
            <li class="px-5 py-3 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-medium truncate">{{ $shift->staff->user->name ?? 'Staff' }}</div>
                    <div class="text-xs text-gray-500">{{ $shift->label() }}@if($sites->count() > 1) · {{ $shift->site->name ?? '' }}@endif</div>
                </div>
                <span class="text-xs px-2 py-1 rounded-full whitespace-nowrap {{ $badge }}">{{ $state }}</span>
            </li>
            @empty
            <li class="px-5 py-6 text-sm text-gray-500">Nobody is rostered today. <a href="{{ route('staff.team.roster', array_filter(['site' => $selectedSite])) }}" class="text-primary hover:underline">Plan shifts</a>.</li>
            @endforelse
        </ul>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="px-5 py-3 border-b font-semibold">On duty now</div>
        <ul class="divide-y">
            @forelse($onDuty as $record)
            <li class="px-5 py-3 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-medium truncate">{{ $record->staff->user->name ?? 'Staff' }}</div>
                    <div class="text-xs text-gray-500">{{ $record->staff->primaryAssignment?->position->name }} · since {{ $record->clock_in_at->format('g:i A') }}@if($sites->count() > 1) · {{ $record->site->name }}@endif</div>
                </div>
                <div class="flex flex-wrap justify-end gap-1">
                    @forelse($record->flags() as $flag)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-orange-100 text-orange-800">{{ $flag }}</span>
                    @empty
                    <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800">On site</span>
                    @endforelse
                </div>
            </li>
            @empty
            <li class="px-5 py-6 text-sm text-gray-500">Nobody is clocked in.</li>
            @endforelse
        </ul>
    </div>
</div>

@foreach([['Open tasks', $tasks, 'staff.team.tasks.assign', 'due'], ['Open repairs', $repairs, 'staff.team.repairs.assign', 'repair']] as [$heading, $items, $route, $kind])
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-5 py-3 border-b font-semibold">{{ $heading }} <span class="text-gray-400 font-normal">({{ $items->count() }})</span></div>
    <ul class="divide-y">
        @forelse($items as $item)
        @php $team = $teamBySite->get($item->property->site_id, collect()); @endphp
        <li class="px-5 py-3 flex flex-col md:flex-row md:items-center gap-3 {{ $item->escalation_level ? 'bg-orange-50/60' : '' }}">
            <div class="flex-1 min-w-0">
                <div class="font-medium">
                    {{ $item->title }}
                    @if($item->escalation_level)<span class="ml-1 text-xs px-2 py-0.5 rounded-full bg-orange-100 text-orange-800"><i class="fas fa-exclamation-triangle"></i> Overdue</span>@endif
                </div>
                <div class="text-xs text-gray-500">
                    {{ $item->property->title }} · {{ ucfirst($item->priority) }} ·
                    {{ str_replace('_', ' ', $item->status) }} ·
                    @if($kind === 'due' && $item->due_date) due {{ $item->due_date->format('D j M') }} · @endif
                    {{ $item->created_at->diffForHumans() }}
                </div>
            </div>
            <form method="POST" action="{{ route($route, $item) }}" class="flex gap-2 md:w-80">
                @csrf @method('PATCH')
                <select name="assigned_to" required class="flex-1 min-w-0 border rounded-lg px-2 py-1.5 text-sm">
                    <option value="">{{ $item->assignedTo ? $item->assignedTo->name : 'Unassigned — choose…' }}</option>
                    @foreach($team as $member)
                    @continue($member->id === $item->assigned_to)
                    <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->staff->activeAssignments->firstWhere('site_id', $item->property->site_id)?->position->name }})</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-primary text-white text-sm hover:bg-primary/90">{{ $item->assignedTo ? 'Reassign' : 'Assign' }}</button>
            </form>
        </li>
        @empty
        <li class="px-5 py-6 text-sm text-gray-500">Nothing open.</li>
        @endforelse
    </ul>
</div>
@endforeach

@if($handovers->isNotEmpty())
<div class="bg-white rounded-lg shadow">
    <div class="px-5 py-3 border-b font-semibold">Handover notes (last 24 hours)</div>
    <ul class="divide-y">
        @foreach($handovers as $note)
        <li class="px-5 py-3 text-sm">
            <span class="font-medium">{{ $note->staff->user->name ?? 'Staff' }}</span>
            <span class="text-gray-500">· {{ $note->site->name }} · {{ $note->clock_out_at->format('D g:i A') }}</span>
            <p class="text-gray-700 whitespace-pre-line">{{ $note->handover_note }}</p>
        </li>
        @endforeach
    </ul>
</div>
@endif
@endsection
