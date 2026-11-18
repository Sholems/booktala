@extends($layout)

@section('title', 'New Front-Desk Booking')

@section('content')
@php
    $controlBase = 'block min-h-12 w-full rounded-xl border bg-white px-3.5 py-3 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-4 focus:ring-primary/10 disabled:cursor-not-allowed disabled:bg-slate-100';
    $controlClass = fn (string $field, bool $withMargin = true) => ($withMargin ? 'mt-2 ' : '').$controlBase.' '.($errors->has($field) ? 'border-red-500 focus:border-red-500 focus:ring-red-100' : 'border-slate-300');
    $fieldLabels = [
        'site_id' => 'Site', 'unit_id' => 'Unit', 'booking_source' => 'Booking source',
        'guest_name' => 'Guest name', 'guest_phone' => 'Phone number', 'guest_email' => 'Email address',
        'guests' => 'Number of guests', 'check_in' => 'Check-in date', 'check_out' => 'Check-out date',
        'payment_method' => 'Payment method', 'amount' => 'Amount received', 'reference' => 'Payment reference',
        'discount_code' => 'Discount code', 'booking' => 'Booking',
    ];
@endphp

<div class="mx-auto max-w-5xl space-y-6 pb-10">
    <header class="rounded-2xl bg-gradient-to-r from-slate-900 to-primary px-5 py-6 text-white shadow-lg sm:px-7 sm:py-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-200">Front desk</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">New front-desk booking</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200 sm:text-base">Create a walk-in or phone reservation, confirm availability, and record the guest's payment in one flow.</p>
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-slate-100">
                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                <span>Server-verified pricing</span>
            </div>
        </div>
    </header>

    @if($errors->any())
        <div id="booking-errors" role="alert" tabindex="-1" class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-900 shadow-sm">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle mt-0.5 text-red-600" aria-hidden="true"></i>
                <div>
                    <h2 class="font-bold">There is a problem with your booking</h2>
                    <p class="mt-1 text-sm text-red-700">Review the highlighted field{{ $errors->count() === 1 ? '' : 's' }} and try again.</p>
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm">
                        @foreach($errors->messages() as $field => $messages)
                            @foreach($messages as $message)
                                <li><a class="font-medium underline decoration-red-300 underline-offset-2 hover:decoration-red-700" href="#{{ $field === 'booking' ? 'front-desk-booking' : $field }}">{{ $fieldLabels[$field] ?? str($field)->replace('_', ' ')->title() }}: {{ $message }}</a></li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form id="front-desk-booking" method="POST" action="{{ route('frontdesk.reservations.store') }}" class="space-y-6">
        @csrf

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="stay-heading">
            <div class="mb-6 flex items-start gap-3 border-b border-slate-100 pb-5">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 font-bold text-primary">1</span>
                <div>
                    <h2 id="stay-heading" class="text-lg font-bold text-slate-900">Stay details</h2>
                    <p class="mt-1 text-sm text-slate-500">Choose the location, unit, dates, and number of guests.</p>
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="site_id" class="block text-sm font-semibold text-slate-700">Site <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="site_id" name="site_id" class="{{ $controlClass('site_id') }}" required aria-invalid="{{ $errors->has('site_id') ? 'true' : 'false' }}">
                        <option value="">Select site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>{{ $site->name }}</option>
                        @endforeach
                    </select>
                    @error('site_id')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="unit_id" class="block text-sm font-semibold text-slate-700">Unit <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="unit_id" name="unit_id" class="{{ $controlClass('unit_id') }}" required aria-invalid="{{ $errors->has('unit_id') ? 'true' : 'false' }}">
                        <option value="">Select unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" data-site="{{ $unit->site_id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->title }} — {{ $unit->site->name }}</option>
                        @endforeach
                    </select>
                    @error('unit_id')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="booking_source" class="block text-sm font-semibold text-slate-700">Booking source</label>
                    <select id="booking_source" name="booking_source" class="{{ $controlClass('booking_source') }}" aria-invalid="{{ $errors->has('booking_source') ? 'true' : 'false' }}">
                        <option value="walk_in" @selected(old('booking_source', 'walk_in') === 'walk_in')>Walk-in</option>
                        <option value="phone" @selected(old('booking_source') === 'phone')>Phone</option>
                    </select>
                    @error('booking_source')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="guests" class="block text-sm font-semibold text-slate-700">Number of guests <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="guests" name="guests" type="number" min="1" value="{{ old('guests', 1) }}" class="{{ $controlClass('guests') }}" required aria-invalid="{{ $errors->has('guests') ? 'true' : 'false' }}">
                    @error('guests')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="check_in" class="block text-sm font-semibold text-slate-700">Check-in <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="check_in" name="check_in" type="date" min="{{ today()->toDateString() }}" value="{{ old('check_in', today()->toDateString()) }}" class="{{ $controlClass('check_in') }}" required aria-invalid="{{ $errors->has('check_in') ? 'true' : 'false' }}">
                    @error('check_in')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="check_out" class="block text-sm font-semibold text-slate-700">Check-out <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="check_out" name="check_out" type="date" min="{{ today()->addDay()->toDateString() }}" value="{{ old('check_out', today()->addDay()->toDateString()) }}" class="{{ $controlClass('check_out') }}" required aria-invalid="{{ $errors->has('check_out') ? 'true' : 'false' }}">
                    @error('check_out')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="guest-heading">
            <div class="mb-6 flex items-start gap-3 border-b border-slate-100 pb-5">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 font-bold text-primary">2</span>
                <div>
                    <h2 id="guest-heading" class="text-lg font-bold text-slate-900">Guest information</h2>
                    <p class="mt-1 text-sm text-slate-500">Enter the guest's contact details or find an existing profile.</p>
                </div>
            </div>

            <input id="guest-id" name="guest_id" type="hidden" value="{{ old('guest_id') }}">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="guest_name" class="block text-sm font-semibold text-slate-700">Guest name <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="guest_name" name="guest_name" autocomplete="name" value="{{ old('guest_name') }}" class="{{ $controlClass('guest_name') }}" placeholder="Full name" required aria-invalid="{{ $errors->has('guest_name') ? 'true' : 'false' }}">
                    @error('guest_name')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="guest_phone" class="block text-sm font-semibold text-slate-700">Phone number <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="guest_phone" name="guest_phone" type="tel" autocomplete="tel" value="{{ old('guest_phone') }}" class="{{ $controlClass('guest_phone') }}" placeholder="e.g. 0803 123 4567" required aria-invalid="{{ $errors->has('guest_phone') ? 'true' : 'false' }}">
                    @error('guest_phone')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label for="guest_email" class="block text-sm font-semibold text-slate-700">Email address <span class="font-normal text-slate-400">(optional)</span></label>
                    <input id="guest_email" name="guest_email" type="email" autocomplete="email" value="{{ old('guest_email') }}" class="{{ $controlClass('guest_email') }}" placeholder="guest@example.com" aria-invalid="{{ $errors->has('guest_email') ? 'true' : 'false' }}">
                    @error('guest_email')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:flex sm:items-center sm:justify-between sm:gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-800">Check for an existing guest</p>
                    <p class="mt-1 text-sm text-slate-500">Search by the exact phone number or email to avoid duplicate profiles.</p>
                </div>
                <button id="find-guest" type="button" class="mt-4 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-primary/10 sm:mt-0 sm:w-auto">
                    <i class="fas fa-search" aria-hidden="true"></i><span>Find guest</span>
                </button>
            </div>
            <div id="guest-results" class="mt-3 hidden space-y-2 text-sm" aria-live="polite"></div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="payment-heading">
            <div class="mb-6 flex items-start gap-3 border-b border-slate-100 pb-5">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 font-bold text-primary">3</span>
                <div>
                    <h2 id="payment-heading" class="text-lg font-bold text-slate-900">Pricing and payment</h2>
                    <p class="mt-1 text-sm text-slate-500">Calculate the official quote, then record the amount and payment evidence received.</p>
                </div>
            </div>

            <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-4 sm:flex sm:items-center sm:justify-between sm:gap-4">
                <div>
                    <p class="font-semibold text-slate-900">Server quote</p>
                    <p class="mt-1 text-sm leading-5 text-slate-600">Confirms availability and includes VAT, the ₦2,000 processing fee, and the refundable ₦20,000 caution fee.</p>
                </div>
                <button id="calculate-quote" type="button" class="mt-4 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-primary/20 sm:mt-0 sm:w-auto">
                    <i class="fas fa-calculator" aria-hidden="true"></i><span>Calculate quote</span>
                </button>
            </div>
            <div id="quote-result" class="mt-4 hidden rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700" aria-live="polite"></div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label for="payment_method" class="block text-sm font-semibold text-slate-700">Payment method <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="payment_method" name="payment_method" class="{{ $controlClass('payment_method') }}" required aria-invalid="{{ $errors->has('payment_method') ? 'true' : 'false' }}">
                        <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                        <option value="pos" @selected(old('payment_method') === 'pos')>POS</option>
                        <option value="transfer" @selected(old('payment_method') === 'transfer')>Bank transfer</option>
                    </select>
                    @error('payment_method')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="amount" class="block text-sm font-semibold text-slate-700">Amount received <span class="text-red-600" aria-hidden="true">*</span></label>
                    <div class="relative mt-2">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 font-semibold text-slate-500">₦</span>
                        <input id="amount" name="amount" type="number" min="0.01" step="0.01" inputmode="decimal" value="{{ old('amount') }}" class="{{ $controlClass('amount', false) }} pl-9" placeholder="0.00" required aria-invalid="{{ $errors->has('amount') ? 'true' : 'false' }}">
                    </div>
                    <p class="mt-2 text-sm text-slate-500">Partial payments are allowed; the receipt will show the remaining balance.</p>
                    @error('amount')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                <div id="reference-field" @class(['md:col-span-2', 'hidden' => old('payment_method', 'cash') === 'cash'])>
                    <label for="reference" class="block text-sm font-semibold text-slate-700">Payment reference <span id="reference-required" class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="reference" name="reference" autocomplete="off" value="{{ old('reference') }}" class="{{ $controlClass('reference') }}" placeholder="Enter the POS or transfer reference" aria-describedby="reference-help reference-error" aria-invalid="{{ $errors->has('reference') ? 'true' : 'false' }}">
                    <p id="reference-help" class="mt-2 text-sm text-slate-500">Required for POS and bank transfer payments so the transaction can be reconciled.</p>
                    @error('reference')<p id="reference-error" class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                    @if(! $errors->has('reference'))<span id="reference-error"></span>@endif
                </div>

                <div class="md:col-span-2">
                    <label for="discount_code" class="block text-sm font-semibold text-slate-700">Discount code <span class="font-normal text-slate-400">(optional)</span></label>
                    <input id="discount_code" name="discount_code" autocomplete="off" value="{{ old('discount_code') }}" class="{{ $controlClass('discount_code') }}" placeholder="Enter a valid code" aria-invalid="{{ $errors->has('discount_code') ? 'true' : 'false' }}">
                    @error('discount_code')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            @can('reservation.manage')
                <label class="mt-6 flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <input type="checkbox" name="check_in_now" value="1" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-primary focus:ring-primary" @checked(old('check_in_now'))>
                    <span><span class="block text-sm font-semibold text-slate-800">Check in immediately</span><span class="mt-1 block text-sm text-slate-500">Available only when the booking is fully paid and starts today.</span></span>
                </label>
            @endcan
        </section>

        <div class="flex flex-col-reverse gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
            <p class="text-sm text-slate-500"><span class="text-red-600">*</span> Required fields</p>
            <button id="submit-booking" type="submit" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-accent px-6 py-3 text-base font-bold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-200 disabled:cursor-wait disabled:opacity-70 sm:w-auto">
                <i class="fas fa-check" aria-hidden="true"></i><span>Create booking and record payment</span>
            </button>
        </div>
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

const paymentMethodSelect = document.querySelector('[name="payment_method"]');
const referenceField = document.getElementById('reference-field');
const referenceInput = document.getElementById('reference');
const syncReferenceField = () => {
    const requiresReference = ['pos', 'transfer'].includes(paymentMethodSelect.value);
    referenceField.classList.toggle('hidden', !requiresReference);
    referenceInput.required = requiresReference;
    referenceInput.setAttribute('aria-required', requiresReference ? 'true' : 'false');
};
paymentMethodSelect.addEventListener('change', syncReferenceField);
syncReferenceField();

const checkInInput = document.querySelector('[name="check_in"]');
const checkOutInput = document.querySelector('[name="check_out"]');
const syncCheckoutMinimum = () => {
    if (!checkInInput.value) return;
    const [year, month, day] = checkInInput.value.split('-').map(Number);
    const nextDay = new Date(Date.UTC(year, month - 1, day + 1));
    checkOutInput.min = nextDay.toISOString().slice(0, 10);
};
checkInInput.addEventListener('change', syncCheckoutMinimum);
syncCheckoutMinimum();

document.getElementById('calculate-quote').addEventListener('click', async function () {
    const form = document.getElementById('front-desk-booking');
    const result = document.getElementById('quote-result');
    const label = this.querySelector('span');
    const originalLabel = label.textContent;
    const fields = ['site_id', 'unit_id', 'check_in', 'check_out', 'guests', 'discount_code'];
    const body = new FormData();
    body.append('_token', form.querySelector('[name="_token"]').value);
    fields.forEach(name => body.append(name, form.querySelector(`[name="${name}"]`)?.value || ''));
    result.classList.remove('hidden');
    result.textContent = 'Calculating…';
    this.disabled = true;
    label.textContent = 'Calculating…';

    try {
        const response = await fetch(@json(route('frontdesk.reservations.quote')), { method: 'POST', body, headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Unable to calculate quote.');
        const money = value => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(value);
        result.innerHTML = `<div class="grid grid-cols-2 gap-2"><span>Accommodation</span><strong class="text-right">${money(data.subtotal)}</strong><span>Discount</span><strong class="text-right">-${money(data.discount_amount)}</strong><span>VAT</span><strong class="text-right">${money(data.tax_amount)}</strong><span>Processing fee</span><strong class="text-right">${money(data.processing_fee)}</strong><span>Refundable caution fee</span><strong class="text-right">${money(data.caution_fee)}</strong><span class="border-t pt-2 font-bold">Total</span><strong class="border-t pt-2 text-right">${money(data.total_amount)}</strong></div>`;
        form.querySelector('[name="amount"]').value = data.total_amount;
    } catch (error) {
        result.textContent = error.message;
    } finally {
        this.disabled = false;
        label.textContent = originalLabel;
    }
});

document.getElementById('find-guest').addEventListener('click', async function () {
    const form = document.getElementById('front-desk-booking');
    const result = document.getElementById('guest-results');
    const label = this.querySelector('span');
    const originalLabel = label.textContent;
    const params = new URLSearchParams({ phone: form.querySelector('[name="guest_phone"]').value, email: form.querySelector('[name="guest_email"]').value });
    result.classList.remove('hidden');
    result.textContent = 'Searching…';
    this.disabled = true;
    label.textContent = 'Searching…';
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
    } finally {
        this.disabled = false;
        label.textContent = originalLabel;
    }
});

document.querySelectorAll('[name="guest_phone"], [name="guest_email"]').forEach(input => input.addEventListener('input', function () {
    document.getElementById('guest-id').value = '';
}));

document.getElementById('front-desk-booking').addEventListener('submit', function (event) {
    queueMicrotask(() => {
        if (event.defaultPrevented) return;
        const button = document.getElementById('submit-booking');
        button.disabled = true;
        button.querySelector('span').textContent = 'Creating booking…';
    });
});

document.getElementById('booking-errors')?.focus();
</script>
@endpush
