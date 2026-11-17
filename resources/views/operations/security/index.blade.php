@extends($layout)
@section('page_title', 'Security')
@section('content')
@php
    $field = 'w-full border rounded-lg px-3 py-2 text-sm';
    $interval = $site->patrol_interval_minutes;
@endphp
<div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h2 class="text-xl font-bold">{{ $site->name }} · Security</h2>
        <p class="text-sm text-gray-500">{{ $onSite->count() }} {{ Str::plural('visitor', $onSite->count()) }} on site · {{ $incidents->where('status', 'open')->count() }} open {{ Str::plural('incident', $incidents->where('status', 'open')->count()) }}</p>
    </div>
    @include('operations._site-picker')
</div>

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    {{-- Visitors on site --}}
    <div class="xl:col-span-2 bg-white rounded-lg shadow">
        <div class="px-5 py-3 border-b font-semibold">Visitors on site now</div>
        <ul class="divide-y">
            @forelse($onSite as $visitor)
            <li class="px-5 py-3 flex flex-col sm:flex-row sm:items-center gap-2 justify-between">
                <div class="min-w-0">
                    <div class="font-medium">{{ $visitor->visitor_name }} @if($visitor->vehicle_plate)<span class="text-xs text-gray-500">· {{ $visitor->vehicle_plate }}</span>@endif</div>
                    <div class="text-xs text-gray-500">
                        {{ \App\Models\VisitorLog::PURPOSES[$visitor->purpose] ?? $visitor->purpose }}
                        @if($visitor->property) · {{ $visitor->property->title }}@endif
                        @if($visitor->host) · to see {{ $visitor->host }}@endif
                        · in since {{ $visitor->arrived_at->format('g:i A') }}
                        @if($visitor->arrived_at->lt(today()))<span class="text-orange-600">({{ $visitor->arrived_at->format('D j M') }})</span>@endif
                    </div>
                </div>
                <form method="POST" action="{{ route($p . '.security.visitors.out', $visitor) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-white ring-1 ring-gray-300 text-sm hover:ring-primary"><i class="fas fa-sign-out-alt mr-1"></i> Sign out</button>
                </form>
            </li>
            @empty
            <li class="px-5 py-6 text-sm text-gray-500">No visitors on site.</li>
            @endforelse
        </ul>
    </div>

    {{-- Sign a visitor in --}}
    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold mb-3">Sign a visitor in</h3>
        <form method="POST" action="{{ route($p . '.security.visitors.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="site_id" value="{{ $site->id }}">
            <input type="text" name="visitor_name" required maxlength="150" placeholder="Visitor's full name" value="{{ old('visitor_name') }}" class="{{ $field }}">
            <div class="grid grid-cols-2 gap-2">
                <input type="tel" name="phone" maxlength="30" placeholder="Phone" value="{{ old('phone') }}" class="{{ $field }}">
                <input type="text" name="vehicle_plate" maxlength="20" placeholder="Vehicle plate" value="{{ old('vehicle_plate') }}" class="{{ $field }}">
            </div>
            <select name="purpose" required class="{{ $field }}">
                @foreach(\App\Models\VisitorLog::PURPOSES as $value => $label)
                <option value="{{ $value }}" @selected(old('purpose') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if($properties->count() > 1)
            <select name="property_id" class="{{ $field }}">
                <option value="">Which flat? (optional)</option>
                @foreach($properties as $property)
                <option value="{{ $property->id }}" @selected((int) old('property_id') === $property->id)>{{ $property->title }}</option>
                @endforeach
            </select>
            @endif
            <input type="text" name="host" maxlength="150" placeholder="Visiting whom? (optional)" value="{{ old('host') }}" class="{{ $field }}">
            <select name="id_type" class="{{ $field }}">
                <option value="">ID shown…</option>
                @foreach(\App\Models\VisitorLog::ID_TYPES as $value => $label)
                <option value="{{ $value }}" @selected(old('id_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400">Record which ID was shown, not its number.</p>
            <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90"><i class="fas fa-sign-in-alt mr-1"></i> Sign in</button>
        </form>
    </div>
</div>

{{-- Patrols --}}
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-5 py-3 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div class="font-semibold">Patrol checkpoints</div>
        <div class="text-xs text-gray-500">
            @if($interval)
            Due every {{ $interval }} min{{ $site->patrol_from ? ', ' . substr($site->patrol_from, 0, 5) . '–' . substr($site->patrol_until, 0, 5) : ', round the clock' }}. Overdue after {{ $interval * 2 }} min.
            @else
            Patrol checks are off for this site.
            @endif
        </div>
    </div>
    <ul class="divide-y">
        @forelse($checkpoints as $checkpoint)
        @php
            $last = $checkpoint->lastScan;
            $overdue = $interval && $site->patrolsAt(now()) && (!$last || $last->scanned_at->lt(now()->subMinutes($interval * 2)));
        @endphp
        <li class="px-5 py-3 flex items-center justify-between gap-3 {{ $overdue ? 'bg-orange-50/60' : '' }}">
            <div>
                <div class="font-medium">{{ $checkpoint->name }}</div>
                <div class="text-xs {{ $overdue ? 'text-orange-700' : 'text-gray-500' }}">
                    @if($last) Last visit {{ $last->scanned_at->diffForHumans() }} by {{ $last->staff->user->name ?? 'staff' }} @else Not visited yet @endif
                    @if($overdue) · overdue @endif
                </div>
            </div>
            @if($canManage)
            <form method="POST" action="{{ route($p . '.security.checkpoints.remove', $checkpoint) }}" onsubmit="return confirm('Remove this checkpoint?')">
                @csrf @method('PATCH')
                <button type="submit" class="text-xs text-red-600 hover:underline">Remove</button>
            </form>
            @endif
        </li>
        @empty
        <li class="px-5 py-6 text-sm text-gray-500">No checkpoints yet.</li>
        @endforelse
    </ul>
    @if($canManage)
    <div class="px-5 py-4 border-t grid grid-cols-1 lg:grid-cols-2 gap-4">
        <form method="POST" action="{{ route($p . '.security.checkpoints.store') }}" class="flex gap-2">
            @csrf
            <input type="hidden" name="site_id" value="{{ $site->id }}">
            <input type="text" name="name" required maxlength="100" placeholder="New checkpoint, e.g. Back gate" class="flex-1 min-w-0 border rounded-lg px-3 py-2 text-sm">
            <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white text-sm hover:bg-primary/90">Add</button>
        </form>
        <form method="POST" action="{{ route($p . '.security.patrol-settings', $site) }}" class="flex flex-wrap gap-2 items-center text-sm">
            @csrf @method('PATCH')
            <label class="text-gray-600">Every</label>
            <input type="number" name="patrol_interval_minutes" min="15" max="720" value="{{ $interval }}" placeholder="off" class="w-20 border rounded-lg px-2 py-2">
            <span class="text-gray-600">min, from</span>
            <input type="time" name="patrol_from" value="{{ $site->patrol_from ? substr($site->patrol_from, 0, 5) : '' }}" class="border rounded-lg px-2 py-2">
            <span class="text-gray-600">to</span>
            <input type="time" name="patrol_until" value="{{ $site->patrol_until ? substr($site->patrol_until, 0, 5) : '' }}" class="border rounded-lg px-2 py-2">
            <button type="submit" class="px-3 py-2 rounded-lg bg-white ring-1 ring-gray-300 hover:ring-primary">Save</button>
        </form>
        @if($checkpoints->isNotEmpty())
        <a href="{{ route($p . '.security.checkpoints.print', ['site' => $site->id]) }}" target="_blank" class="text-sm text-primary hover:underline"><i class="fas fa-qrcode mr-1"></i> Print QR codes for these checkpoints</a>
        @endif
    </div>
    @endif
</div>

{{-- Incidents --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div class="xl:col-span-2 bg-white rounded-lg shadow">
        <div class="px-5 py-3 border-b font-semibold">Incidents <span class="text-gray-400 font-normal">(open, and last 7 days)</span></div>
        <ul class="divide-y">
            @forelse($incidents as $incident)
            <li>
                <a href="{{ route($p . '.security.incidents.show', $incident) }}" class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50">
                    <div class="min-w-0">
                        <div class="font-medium truncate">{{ $incident->title }}</div>
                        <div class="text-xs text-gray-500">{{ \App\Models\Incident::CATEGORIES[$incident->category] ?? $incident->category }} · {{ $incident->occurred_at->format('D j M, g:i A') }}@if($incident->property) · {{ $incident->property->title }}@endif</div>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $incident->severityBadge() }}">{{ ucfirst($incident->severity) }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $incident->status === 'open' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">{{ ucfirst($incident->status) }}</span>
                    </div>
                </a>
            </li>
            @empty
            <li class="px-5 py-6 text-sm text-gray-500">No incidents.</li>
            @endforelse
        </ul>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold mb-3">Report an incident</h3>
        <form method="POST" action="{{ route($p . '.security.incidents.store') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="hidden" name="site_id" value="{{ $site->id }}">
            <input type="text" name="title" required maxlength="150" placeholder="What happened? (short)" value="{{ old('title') }}" class="{{ $field }}">
            <div class="grid grid-cols-2 gap-2">
                <select name="category" required class="{{ $field }}">
                    @foreach(\App\Models\Incident::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="severity" required class="{{ $field }}">
                    @foreach(\App\Models\Incident::SEVERITIES as $value => $label)
                    <option value="{{ $value }}" @selected(old('severity', 'medium') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <input type="datetime-local" name="occurred_at" required max="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" class="{{ $field }}">
            @if($properties->count() > 1)
            <select name="property_id" class="{{ $field }}">
                <option value="">Whole site / common area</option>
                @foreach($properties as $property)
                <option value="{{ $property->id }}" @selected((int) old('property_id') === $property->id)>{{ $property->title }}</option>
                @endforeach
            </select>
            @endif
            <textarea name="description" required rows="3" maxlength="5000" placeholder="Describe what happened, who was involved" class="{{ $field }}">{{ old('description') }}</textarea>
            <textarea name="actions_taken" rows="2" maxlength="3000" placeholder="What has been done so far (optional)" class="{{ $field }}">{{ old('actions_taken') }}</textarea>
            <input type="file" name="photo" accept="image/*" capture="environment" class="w-full text-sm">
            <p class="text-xs text-gray-400">High and critical incidents alert your supervisor and head office straight away.</p>
            <button type="submit" class="w-full bg-accent text-white px-4 py-2 rounded-lg hover:bg-accent/90">Report incident</button>
        </form>
    </div>
</div>

{{-- Visitor history --}}
<div class="bg-white rounded-lg shadow">
    <div class="px-5 py-3 border-b flex items-center justify-between gap-3">
        <span class="font-semibold">Signed-out visitors</span>
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="site" value="{{ $site->id }}">
            <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()" class="border rounded-lg px-2 py-1 text-sm">
        </form>
    </div>
    <ul class="divide-y text-sm">
        @forelse($visitors as $visitor)
        <li class="px-5 py-2 flex justify-between gap-3">
            <span>{{ $visitor->visitor_name }} <span class="text-gray-500">· {{ \App\Models\VisitorLog::PURPOSES[$visitor->purpose] ?? '' }}@if($visitor->property) · {{ $visitor->property->title }}@endif</span></span>
            <span class="text-gray-500 whitespace-nowrap">{{ $visitor->arrived_at->format('g:i A') }} – {{ $visitor->left_at->format('g:i A') }}</span>
        </li>
        @empty
        <li class="px-5 py-4 text-gray-500">None on {{ $date->format('j M Y') }}.</li>
        @endforelse
    </ul>
</div>
@endsection
