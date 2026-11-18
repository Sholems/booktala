@extends($layout)

@section('title', 'New Front-Desk Booking')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">New front-desk booking</h1>
        <p class="text-gray-500">Walk-in and phone bookings use the same availability and price calculation as online bookings.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 p-4 text-green-700">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-red-50 p-4 text-red-700">{{ $errors->first() }}</div>
    @endif

    <form id="front-desk-booking" method="POST" action="{{ route('frontdesk.reservations.store') }}" class="rounded-xl bg-white p-6 shadow space-y-5">
        @csrf
        <div class="grid md:grid-cols-2 gap-4">
            <label class="block">Site<select name="site_id" class="mt-1 w-full rounded border-gray-300" required><option value="">Select site</option>@foreach($sites as $site)<option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>{{ $site->name }}</option>@endforeach</select></label>
            <label class="block">Unit<select name="unit_id" class="mt-1 w-full rounded border-gray-300" required><option value="">Select unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}" data-site="{{ $unit->site_id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->title }} — {{ $unit->site->name }}</option>@endforeach</select></label>
            <label class="block">Booking source<select name="booking_source" class="mt-1 w-full rounded border-gray-300"><option value="walk_in" @selected(old('booking_source', 'walk_in') === 'walk_in')>Walk-in</option><option value="phone" @selected(old('booking_source') === 'phone')>Phone</option></select></label>
            <input id="guest-id" name="guest_id" type="hidden" value="{{ old('guest_id') }}">
            <label class="block">Guest name<input name="guest_name" value="{{ old('guest_name') }}" class="mt-1 w-full rounded border-gray-300" required></label>
            <label class="block">Phone<input name="guest_phone" value="{{ old('guest_phone') }}" class="mt-1 w-full rounded border-gray-300" required></label>
            <label class="block">Email<input name="guest_email" type="email" value="{{ old('guest_email') }}" class="mt-1 w-full rounded border-gray-300"></label>
            <label class="block">Guests<input name="guests" type="number" min="1" value="{{ old('guests', 1) }}" class="mt-1 w-full rounded border-gray-300" required></label>
            <label class="block">Check-in<input name="check_in" type="date" value="{{ old('check_in', today()->toDateString()) }}" class="mt-1 w-full rounded border-gray-300" required></label>
            <label class="block">Check-out<input name="check_out" type="date" value="{{ old('check_out', today()->addDay()->toDateString()) }}" class="mt-1 w-full rounded border-gray-300" required></label>
            <label class="block">Payment method<select name="payment_method" class="mt-1 w-full rounded border-gray-300" required><option value="cash" @selected(old('payment_method') === 'cash')>Cash</option><option value="pos" @selected(old('payment_method') === 'pos')>POS</option><option value="transfer" @selected(old('payment_method') === 'transfer')>Bank transfer</option></select></label>
            <label class="block">Amount received<input name="amount" type="number" step="0.01" value="{{ old('amount') }}" class="mt-1 w-full rounded border-gray-300" required></label>
            <label class="block">Reference (POS/transfer)<input name="reference" value="{{ old('reference') }}" class="mt-1 w-full rounded border-gray-300"></label>
            <label class="block">Discount code<input name="discount_code" value="{{ old('discount_code') }}" class="mt-1 w-full rounded border-gray-300"></label>
        </div>
        <div class="rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between gap-4"><p class="text-sm text-gray-600">Search exact phone or email before creating a duplicate guest.</p><button id="find-guest" type="button" class="rounded border px-3 py-2 text-sm">Find guest</button></div>
            <div id="guest-results" class="mt-3 hidden space-y-2 text-sm"></div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="flex items-center justify-between gap-4">
                <div><p class="font-medium text-gray-800">Server quote</p><p class="text-sm text-gray-500">Recalculates availability, VAT, processing, and refundable caution fees.</p></div>
                <button id="calculate-quote" type="button" class="rounded-lg border border-indigo-600 px-4 py-2 text-indigo-700">Calculate quote</button>
            </div>
            <div id="quote-result" class="mt-3 hidden text-sm"></div>
        </div>
        @can('reservation.manage')
            <label class="flex items-center gap-2"><input type="checkbox" name="check_in_now" value="1" @checked(old('check_in_now'))> Check in immediately after full payment</label>
        @endcan
        <button class="rounded-lg bg-indigo-600 px-5 py-2 font-medium text-white">Create booking and record payment</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
const escapeHtml = value => {
    const element = document.createElement('div');
    element.textContent = value || '';
    return element.innerHTML;
};

const siteSelect = document.querySelector('[name="site_id"]');
const unitSelect = document.querySelector('[name="unit_id"]');
const filterUnits = () => {
    const siteId = siteSelect.value;
    Array.from(unitSelect.options).forEach(option => {
        if (!option.value) return;
        option.hidden = Boolean(siteId) && option.dataset.site !== siteId;
        option.disabled = option.hidden;
    });
    if (unitSelect.selectedOptions[0]?.disabled) unitSelect.value = '';
};
siteSelect.addEventListener('change', filterUnits);
filterUnits();

document.getElementById('calculate-quote').addEventListener('click', async function () {
    const form = document.getElementById('front-desk-booking');
    const result = document.getElementById('quote-result');
    const fields = ['site_id', 'unit_id', 'check_in', 'check_out', 'guests', 'discount_code'];
    const body = new FormData();
    body.append('_token', form.querySelector('[name="_token"]').value);
    fields.forEach(name => body.append(name, form.querySelector(`[name="${name}"]`)?.value || ''));
    result.classList.remove('hidden');
    result.textContent = 'Calculating…';

    try {
        const response = await fetch(@json(route('frontdesk.reservations.quote')), { method: 'POST', body, headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Unable to calculate quote.');
        const money = value => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(value);
        result.innerHTML = `<div class="grid grid-cols-2 gap-2"><span>Accommodation</span><strong class="text-right">${money(data.subtotal)}</strong><span>Discount</span><strong class="text-right">-${money(data.discount_amount)}</strong><span>VAT</span><strong class="text-right">${money(data.tax_amount)}</strong><span>Processing fee</span><strong class="text-right">${money(data.processing_fee)}</strong><span>Refundable caution fee</span><strong class="text-right">${money(data.caution_fee)}</strong><span class="border-t pt-2 font-bold">Total</span><strong class="border-t pt-2 text-right">${money(data.total_amount)}</strong></div>`;
        form.querySelector('[name="amount"]').value = data.total_amount;
    } catch (error) {
        result.textContent = error.message;
    }
});

document.getElementById('find-guest').addEventListener('click', async function () {
    const form = document.getElementById('front-desk-booking');
    const result = document.getElementById('guest-results');
    const params = new URLSearchParams({ phone: form.querySelector('[name="guest_phone"]').value, email: form.querySelector('[name="guest_email"]').value });
    result.classList.remove('hidden');
    result.textContent = 'Searching…';
    try {
        const response = await fetch(@json(route('frontdesk.guests.index')) + '?' + params, { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Unable to search guests.');
        if (!data.guests.length) {
            document.getElementById('guest-id').value = '';
            result.textContent = 'No existing guest found. A new guest will be created.';
            return;
        }
        result.innerHTML = data.guests.map(guest => `<label class="flex cursor-pointer items-start gap-2 rounded border bg-gray-50 p-3"><input type="radio" name="guest_choice" value="${Number(guest.id)}"><span><strong>${escapeHtml(guest.name)}</strong><br>${escapeHtml(guest.phone)}${guest.email ? ' · ' + escapeHtml(guest.email) : ''}</span></label>`).join('');
        result.querySelectorAll('[name="guest_choice"]').forEach(choice => choice.addEventListener('change', function () { document.getElementById('guest-id').value = this.value; }));
    } catch (error) {
        result.textContent = error.message;
    }
});

document.querySelectorAll('[name="guest_phone"], [name="guest_email"]').forEach(input => input.addEventListener('input', function () {
    document.getElementById('guest-id').value = '';
}));
</script>
@endpush
