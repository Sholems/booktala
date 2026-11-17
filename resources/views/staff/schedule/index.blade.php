@extends('layouts.staff')

@section('page_title', 'My Schedule')

@section('content')
@php
    $shiftLabel = fn ($type) => \App\Models\StaffSchedule::shiftTypes()[$type] ?? ucfirst(str_replace('_', ' ', $type));
    $time = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : '';
@endphp
<div class="py-6">
    <div class="px-4 mx-auto max-w-4xl sm:px-6 lg:px-8">
        <h1 class="mb-1 text-2xl font-semibold">My Schedule</h1>
        <p class="mb-6 text-sm text-gray-500">Shifts planned for you by the admin team. Speak to an admin if something needs to change.</p>

        <h2 class="mb-3 text-lg font-semibold">Upcoming shifts</h2>
        <div class="mb-8 bg-white rounded-lg shadow divide-y">
            @forelse($upcoming as $shift)
            <div class="flex items-center justify-between gap-4 p-4 {{ $shift->date->isToday() ? 'bg-blue-50' : '' }}">
                <div>
                    <p class="font-medium">
                        {{ $shift->date->format('l, j M Y') }}
                        @if($shift->date->isToday())<span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-blue-600 text-white">Today</span>@endif
                    </p>
                    <p class="text-sm text-gray-600">{{ $shiftLabel($shift->shift_type) }} · {{ $time($shift->start_time) }} – {{ $time($shift->end_time) }}</p>
                    @if($shift->notes)<p class="mt-1 text-sm text-gray-500">{{ $shift->notes }}</p>@endif
                </div>
                <span class="px-2 py-1 text-xs rounded-full {{ $shift->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">{{ ucfirst(str_replace('_', ' ', $shift->status)) }}</span>
            </div>
            @empty
            <p class="p-6 text-gray-500">No upcoming shifts scheduled.</p>
            @endforelse
        </div>

        @if($recent->isNotEmpty())
        <h2 class="mb-3 text-lg font-semibold">Last two weeks</h2>
        <div class="bg-white rounded-lg shadow divide-y text-sm">
            @foreach($recent as $shift)
            <div class="flex items-center justify-between gap-4 p-3 text-gray-600">
                <span>{{ $shift->date->format('D, j M') }} · {{ $shiftLabel($shift->shift_type) }}</span>
                <span>{{ ucfirst(str_replace('_', ' ', $shift->status)) }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
