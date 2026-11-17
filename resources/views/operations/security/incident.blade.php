@extends($layout)
@section('page_title', 'Incident')
@section('content')
<div class="max-w-3xl">
    <a href="{{ route($p . '.security.index', ['site' => $incident->site_id]) }}" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-arrow-left mr-1"></i> {{ $incident->site->name }} security</a>
    <div class="mt-1 mb-4 flex flex-wrap items-center gap-2">
        <h2 class="text-2xl font-bold">{{ $incident->title }}</h2>
        <span class="text-xs px-2 py-0.5 rounded-full {{ $incident->severityBadge() }}">{{ ucfirst($incident->severity) }}</span>
        <span class="text-xs px-2 py-0.5 rounded-full {{ $incident->status === 'open' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">{{ ucfirst($incident->status) }}</span>
    </div>

    <div class="bg-white rounded-lg shadow p-5 space-y-4 mb-6">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div><dt class="text-gray-500">What</dt><dd>{{ \App\Models\Incident::CATEGORIES[$incident->category] ?? $incident->category }}</dd></div>
            <div><dt class="text-gray-500">When</dt><dd>{{ $incident->occurred_at->format('l j F Y, g:i A') }}</dd></div>
            <div><dt class="text-gray-500">Where</dt><dd>{{ $incident->site->name }}@if($incident->property) · {{ $incident->property->title }}@endif</dd></div>
            <div><dt class="text-gray-500">Reported by</dt><dd>{{ $incident->reportedBy->name ?? '—' }} · {{ $incident->created_at->format('j M, g:i A') }}</dd></div>
        </dl>
        <div>
            <div class="text-sm text-gray-500 mb-1">Description</div>
            <p class="whitespace-pre-line">{{ $incident->description }}</p>
        </div>
        @if($incident->actions_taken)
        <div>
            <div class="text-sm text-gray-500 mb-1">Actions taken</div>
            <p class="whitespace-pre-line">{{ $incident->actions_taken }}</p>
        </div>
        @endif
        @if($incident->photo_path)
        <a href="{{ route($p . '.security.incidents.photo', $incident) }}" target="_blank">
            <img src="{{ route($p . '.security.incidents.photo', $incident) }}" alt="Photo of the incident" class="max-h-80 rounded-lg ring-1 ring-gray-200">
        </a>
        @endif
    </div>

    @if($incident->status === 'resolved')
    <div class="bg-green-50 border border-green-200 rounded-lg p-5">
        <div class="text-sm text-green-800 mb-1">Resolved by {{ $incident->resolvedBy->name ?? '—' }} · {{ $incident->resolved_at->format('j M Y, g:i A') }}</div>
        <p class="whitespace-pre-line">{{ $incident->resolution }}</p>
    </div>
    @elseif($canManage)
    <form method="POST" action="{{ route($p . '.security.incidents.resolve', $incident) }}" class="bg-white rounded-lg shadow p-5 space-y-3">
        @csrf @method('PATCH')
        <label class="block text-sm font-medium text-gray-700">Close this incident</label>
        <textarea name="resolution" required rows="3" maxlength="3000" placeholder="Outcome: police report number, repair arranged, guest moved..." class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('resolution') }}</textarea>
        @error('resolution')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Mark resolved</button>
    </form>
    @else
    <p class="text-sm text-gray-500">Your supervisor will close this incident once it is dealt with.</p>
    @endif
</div>
@endsection
