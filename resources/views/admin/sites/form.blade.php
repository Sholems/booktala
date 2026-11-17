@extends('layouts.admin')
@section('page_title', $site->exists ? 'Manage Site' : 'Add Site')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.sites.index') }}" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-arrow-left mr-1"></i> Back to sites</a>
    <h2 class="mt-1 text-2xl font-bold">{{ $site->exists ? $site->name : 'Add Site' }}</h2>
</div>

@if($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-4">Site details</h3>
        <form method="POST" action="{{ $site->exists ? route('admin.sites.update', $site) : route('admin.sites.store') }}" class="space-y-4">
            @csrf
            @if($site->exists) @method('PUT') @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $site->name) }}" required placeholder="e.g. Lekki Gardens Estate" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <input type="text" name="address" value="{{ old('address', $site->address) }}" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" name="city" value="{{ old('city', $site->city) }}" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <input type="text" name="state" value="{{ old('state', $site->state) }}" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <input type="text" name="country" value="{{ old('country', $site->country) }}" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
            </div>
            <fieldset class="rounded-lg border border-gray-200 p-4">
                <legend class="px-1 text-sm font-medium text-gray-700">Clock-in location</legend>
                <p class="text-xs text-gray-500 mb-3">Staff clock-ins are checked against this point. Easiest: stand at the site with your phone and tap "Use my location".</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Latitude</label>
                        <input type="text" inputmode="decimal" name="latitude" id="site-lat" value="{{ old('latitude', $site->latitude) }}" placeholder="6.4474" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Longitude</label>
                        <input type="text" inputmode="decimal" name="longitude" id="site-lng" value="{{ old('longitude', $site->longitude) }}" placeholder="3.4723" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Radius (metres)</label>
                        <input type="number" name="geofence_radius_m" min="30" max="5000" value="{{ old('geofence_radius_m', $site->geofence_radius_m ?? 200) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <button type="button" id="site-use-location" class="mt-3 text-sm text-primary hover:underline"><i class="fas fa-location-arrow mr-1"></i> Use my location</button>
                <span id="site-location-status" class="ml-2 text-xs text-gray-500"></span>
            </fieldset>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Site phone</label>
                <input type="text" name="phone" value="{{ old('phone', $site->phone) }}" placeholder="Front desk or security post" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Access instructions, generator hours, caretaker contact...">{{ old('notes', $site->notes) }}</textarea>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $site->is_active))> Active
            </label>
            <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg hover:bg-primary/90">{{ $site->exists ? 'Save site' : 'Add site' }}</button>
        </form>
    </div>

    @if($site->exists)
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-1">Properties at this site</h3>
            <p class="text-sm text-gray-500 mb-4">Group apartments in the same building here so the building's staff can serve all of them.</p>
            <ul class="divide-y mb-4">
                @forelse($site->properties as $property)
                <li class="py-2 flex justify-between gap-3 text-sm">
                    <a href="{{ route('admin.properties.edit', $property) }}" class="text-primary hover:underline">{{ $property->title }}</a>
                    <span class="text-gray-500">{{ $property->owner->name ?? '—' }}</span>
                </li>
                @empty
                <li class="py-2 text-sm text-gray-500">No properties yet.</li>
                @endforelse
            </ul>
            @if($otherProperties->isNotEmpty())
            <form method="POST" action="{{ route('admin.sites.properties.attach', $site) }}" class="flex flex-col sm:flex-row gap-2">
                @csrf
                <select name="property_id" required class="flex-1 min-w-0 w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Move a property here…</option>
                    @foreach($otherProperties as $property)
                    <option value="{{ $property->id }}">{{ $property->title }} (now: {{ $property->site->name ?? 'no site' }})</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-primary/90">Move</button>
            </form>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-1">Staff on site</h3>
            <p class="text-sm text-gray-500 mb-4">Assign staff from their profile on the Staff page.</p>
            <ul class="divide-y">
                @forelse($site->activeAssignments as $assignment)
                <li class="py-2 flex justify-between gap-3 text-sm">
                    <a href="{{ route('admin.staff.edit', $assignment->staff->user) }}" class="text-primary hover:underline">{{ $assignment->staff->user->name ?? 'Staff' }}</a>
                    <span class="text-gray-600">
                        {{ $assignment->position->name }}
                        @if($assignment->position->level !== 'staff')<span class="ml-1 text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary">{{ $assignment->position->levelLabel() }}</span>@endif
                    </span>
                </li>
                @empty
                <li class="py-2 text-sm text-yellow-700">Nobody is assigned here yet, so no staff member can see this site's guests or tasks.</li>
                @endforelse
            </ul>
        </div>

        @if($site->properties->isEmpty() && $site->activeAssignments->isEmpty())
        <form method="POST" action="{{ route('admin.sites.destroy', $site) }}" onsubmit="return confirm('Delete this empty site?')">
            @csrf @method('DELETE')
            <button type="submit" class="text-red-600 text-sm hover:underline">Delete site</button>
        </form>
        @endif
    </div>
    @endif
</div>

@push('scripts')
<script>
document.getElementById('site-use-location')?.addEventListener('click', function () {
    var status = document.getElementById('site-location-status');
    if (!navigator.geolocation) { status.textContent = 'This browser cannot share location.'; return; }
    status.textContent = 'Getting location…';
    navigator.geolocation.getCurrentPosition(function (pos) {
        document.getElementById('site-lat').value = pos.coords.latitude.toFixed(7);
        document.getElementById('site-lng').value = pos.coords.longitude.toFixed(7);
        status.textContent = 'Set (accurate to about ' + Math.round(pos.coords.accuracy) + ' m). Remember to save.';
    }, function (err) {
        status.textContent = 'Could not get location: ' + err.message;
    }, { enableHighAccuracy: true, timeout: 15000 });
});
</script>
@endpush
@endsection
