{{-- Adds the phone location (if allowed) to any .js-clock-form before it submits. --}}
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
