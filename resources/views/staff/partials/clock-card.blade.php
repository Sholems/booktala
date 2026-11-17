{{-- Clock in / clock out, with the phone's location when the staff member allows it. --}}
<div class="mb-6 rounded-lg bg-white shadow-sm ring-1 ring-gray-100 p-4 md:p-5">
    @if($errors->hasAny(['site_id', 'clock', 'handover_note']))
    <div class="mb-3 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
        @foreach($errors->only(['site_id', 'clock', 'handover_note']) as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    @if($openAttendance)
    <div class="flex flex-col md:flex-row md:items-start gap-4">
        <div class="flex-1">
            <div class="flex items-center gap-2 text-green-700 font-semibold">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></span>
                On duty at {{ $openAttendance->site->name }}
            </div>
            <p class="text-sm text-gray-500 mt-1">
                Since {{ $openAttendance->clock_in_at->format('g:i A') }} · {{ $openAttendance->workedLabel() }} so far
                @if($openAttendance->isLate()) · <span class="text-orange-600">{{ $openAttendance->late_minutes }} min late</span>@endif
            </p>
        </div>
        <form method="POST" action="{{ route('staff.attendance.clock-out') }}" class="js-clock-form flex-1 space-y-2">
            @csrf
            <input type="hidden" name="lat"><input type="hidden" name="lng"><input type="hidden" name="accuracy">
            <label class="block text-sm font-medium text-gray-700">Handover note for the next shift <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea name="handover_note" rows="2" maxlength="2000" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="e.g. Flat 3B guest asked for extra towels; gate light is faulty">{{ old('handover_note') }}</textarea>
            <button type="submit" class="w-full md:w-auto bg-accent text-white px-5 py-2 rounded-lg hover:bg-accent/90">
                <i class="fas fa-sign-out-alt mr-1"></i> Clock out
            </button>
        </form>
    </div>
    @elseif($clockSites->isEmpty())
    <p class="text-sm text-gray-500"><i class="fas fa-info-circle mr-1"></i> You are not assigned to a site yet, so you cannot clock in. Ask your supervisor or head office.</p>
    @else
    <form method="POST" action="{{ route('staff.attendance.clock-in') }}" class="js-clock-form flex flex-col sm:flex-row sm:items-end gap-3">
        @csrf
        <input type="hidden" name="lat"><input type="hidden" name="lng"><input type="hidden" name="accuracy">
        <div class="flex-1">
            <div class="font-semibold text-gray-800">You are off duty</div>
            <p class="text-sm text-gray-500">Clock in when you arrive. Allow location so your supervisor can see you are on site.</p>
        </div>
        @if($clockSites->count() > 1)
        <select name="site_id" class="border rounded-lg px-3 py-2 text-sm">
            @foreach($clockSites as $site)
            <option value="{{ $site->id }}" @selected(($todayShift?->site_id ?? null) === $site->id)>{{ $site->name }}</option>
            @endforeach
        </select>
        @else
        <input type="hidden" name="site_id" value="{{ $clockSites->first()->id }}">
        @endif
        <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700">
            <i class="fas fa-sign-in-alt mr-1"></i> Clock in
        </button>
    </form>
    @endif

    @if($handovers->isNotEmpty())
    <div class="mt-4 border-t pt-3">
        <div class="text-xs font-semibold text-gray-500 uppercase mb-2">Handover notes (last 24 hours)</div>
        <ul class="space-y-2">
            @foreach($handovers as $note)
            <li class="text-sm">
                <span class="font-medium">{{ $note->staff->user->name ?? 'Staff' }}</span>
                <span class="text-gray-500">· {{ $note->site->name }} · {{ $note->clock_out_at->format('D g:i A') }}</span>
                <p class="text-gray-700 whitespace-pre-line">{{ $note->handover_note }}</p>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>

@once
@push('scripts')
<script>
// Attach the phone's location (if allowed) before clocking in or out. Never block on it.
document.querySelectorAll('.js-clock-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (form.dataset.located === '1' || !navigator.geolocation) return;
        event.preventDefault();
        var button = form.querySelector('button[type=submit]');
        if (button) { button.disabled = true; button.dataset.label = button.innerHTML; button.innerHTML = '<i class="fas fa-location-arrow mr-1"></i> Getting location…'; }
        var done = function () { form.dataset.located = '1'; form.submit(); };
        navigator.geolocation.getCurrentPosition(function (pos) {
            form.querySelector('[name=lat]').value = pos.coords.latitude;
            form.querySelector('[name=lng]').value = pos.coords.longitude;
            form.querySelector('[name=accuracy]').value = Math.round(pos.coords.accuracy);
            done();
        }, done, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
    });
});
</script>
@endpush
@endonce
