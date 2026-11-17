@extends('layouts.staff')
@section('page_title', 'Attendance')
@section('content')
<div class="mb-4 flex flex-col md:flex-row md:items-end md:justify-between gap-3">
    <div>
        <h2 class="text-xl font-bold">Attendance</h2>
        <p class="text-sm text-gray-500">{{ $from->format('j M') }} – {{ $to->format('j M Y') }}</p>
    </div>
    <form method="GET" class="flex flex-wrap gap-2 items-end">
        @if($selectedSite)<input type="hidden" name="site" value="{{ $selectedSite }}">@endif
        <div>
            <label class="block text-xs text-gray-500">From</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="border rounded-lg px-3 py-1.5 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500">To</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="border rounded-lg px-3 py-1.5 text-sm">
        </div>
        <button type="submit" class="bg-primary text-white px-4 py-1.5 rounded-lg text-sm">Show</button>
    </form>
</div>

@include('staff.team._site-filter')

@include('partials.attendance-table', ['records' => $records, 'showSite' => $sites->count() > 1])
@endsection
