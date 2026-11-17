@extends('layouts.staff')
@section('page_title', 'Patrol')
@section('content')
<div class="max-w-md mx-auto bg-white rounded-lg shadow p-6 text-center">
    <div class="text-xs uppercase tracking-wide text-gray-500">{{ $checkpoint->site->name }}</div>
    <h2 class="text-2xl font-bold mb-1"><i class="fas fa-map-pin text-accent mr-1"></i>{{ $checkpoint->name }}</h2>
    <p class="text-sm text-gray-500 mb-5">
        @if($checkpoint->lastScan) Last visit {{ $checkpoint->lastScan->scanned_at->diffForHumans() }} by {{ $checkpoint->lastScan->staff->user->name ?? 'staff' }} @else First visit @endif
    </p>
    <form method="POST" action="{{ route('staff.patrol.record', $checkpoint->token) }}" class="js-clock-form space-y-3">
        @csrf
        <input type="hidden" name="lat"><input type="hidden" name="lng"><input type="hidden" name="accuracy">
        <input type="text" name="note" maxlength="255" placeholder="Anything to note? (optional)" class="w-full border rounded-lg px-3 py-2 text-sm">
        <button type="submit" class="w-full bg-green-600 text-white px-5 py-3 rounded-lg text-lg hover:bg-green-700"><i class="fas fa-check mr-1"></i> Record patrol</button>
    </form>
    <a href="{{ route('staff.security.index', ['site' => $checkpoint->site_id]) }}" class="inline-block mt-5 text-sm text-gray-500 hover:text-primary">Report an incident instead</a>
</div>
@include('staff.partials.geolocate-forms')
@endsection
