@extends('layouts.staff')
@section('page_title', 'Roster')
@section('content')
<div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h2 class="text-xl font-bold">{{ $site->name }} roster</h2>
        <p class="text-sm text-gray-500">Week of {{ $weekStart->format('j M Y') }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @if($sites->count() > 1)
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
            <select name="site" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
                @foreach($sites as $s)
                <option value="{{ $s->id }}" @selected($s->id === $site->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </form>
        @endif
        <a href="{{ route('staff.team.roster', ['site' => $site->id, 'week' => $weekStart->subWeek()->toDateString()]) }}" class="px-3 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary" aria-label="Previous week"><i class="fas fa-chevron-left"></i></a>
        <a href="{{ route('staff.team.roster', ['site' => $site->id]) }}" class="px-3 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary">This week</a>
        <a href="{{ route('staff.team.roster', ['site' => $site->id, 'week' => $weekStart->addWeek()->toDateString()]) }}" class="px-3 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary" aria-label="Next week"><i class="fas fa-chevron-right"></i></a>
    </div>
</div>

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<div class="bg-white rounded-lg shadow overflow-x-auto mb-6">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500 sticky left-0 bg-gray-50">Staff</th>
                @foreach($days as $day)
                <th class="px-3 py-3 text-left font-medium {{ $day->isToday() ? 'text-primary' : 'text-gray-500' }} whitespace-nowrap">{{ $day->format('D j') }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($team as $member)
            <tr>
                <td class="px-4 py-3 sticky left-0 bg-white">
                    <div class="font-medium whitespace-nowrap">{{ $member->name }}</div>
                    <div class="text-xs text-gray-500">{{ $member->staff->activeAssignments->firstWhere('site_id', $site->id)?->position->name }}</div>
                </td>
                @foreach($days as $day)
                @php $shift = $shifts->get($member->staff->id . '|' . $day->toDateString())?->first(); @endphp
                <td class="px-3 py-3 align-top">
                    @if($shift && $shift->status !== 'cancelled')
                    <div class="rounded-md px-2 py-1 text-xs {{ $shift->attendance ? 'bg-green-50 text-green-800' : 'bg-blue-50 text-blue-800' }}">
                        <div class="font-medium">{{ \App\Models\StaffSchedule::shiftTypes()[$shift->shift_type] ?? $shift->shift_type }}</div>
                        <div>{{ $shift->startsAt()->format('g:i') }}–{{ $shift->endsAt()->format('g:i A') }}</div>
                        @if($shift->attendance)<div><i class="fas fa-check"></i> In {{ $shift->attendance->clock_in_at->format('g:i A') }}</div>@endif
                    </div>
                    @if(!$shift->attendance && ($day->isToday() || $day->isFuture()))
                    <form method="POST" action="{{ route('staff.team.roster.cancel', $shift) }}" onsubmit="return confirm('Cancel this shift?')">
                        @csrf @method('PATCH')
                        <button type="submit" class="mt-1 text-xs text-red-600 hover:underline">Cancel</button>
                    </form>
                    @endif
                    @elseif($shift)
                    <span class="text-xs text-gray-400 line-through">{{ \App\Models\StaffSchedule::shiftTypes()[$shift->shift_type] ?? '' }}</span>
                    @else
                    <span class="text-xs text-gray-300">—</span>
                    @endif
                </td>
                @endforeach
            </tr>
            @empty
            <tr><td colspan="8" class="px-4 py-6 text-gray-500">Nobody is assigned to this site. Ask head office to assign staff.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($team->isNotEmpty())
<div class="bg-white rounded-lg shadow p-5">
    <h3 class="font-semibold mb-3">Add or change a shift</h3>
    <form method="POST" action="{{ route('staff.team.roster.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
        @csrf
        <input type="hidden" name="site_id" value="{{ $site->id }}">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Staff</label>
            <select name="staff_id" required class="w-full border rounded-lg px-3 py-2 text-sm">
                @foreach($team as $member)
                <option value="{{ $member->staff->id }}" @selected((int) old('staff_id') === $member->staff->id)>{{ $member->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input type="date" name="date" required min="{{ today()->toDateString() }}" value="{{ old('date', max(today(), $weekStart)->toDateString()) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
            <select name="shift_type" required class="w-full border rounded-lg px-3 py-2 text-sm">
                @foreach(\App\Models\StaffSchedule::SHIFT_TIMES as $type => [$start, $end])
                <option value="{{ $type }}" @selected(old('shift_type') === $type)>{{ \App\Models\StaffSchedule::shiftTypes()[$type] }} ({{ $start }}–{{ $end }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <input type="text" name="notes" maxlength="500" value="{{ old('notes') }}" placeholder="e.g. Gate duty" class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">Save shift</button>
    </form>
    <p class="mt-2 text-xs text-gray-500">One shift per person per day: saving again for the same day replaces it.</p>
</div>
@endif
@endsection
