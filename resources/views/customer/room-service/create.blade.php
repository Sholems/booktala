@extends('layouts.customer')

@section('title', 'Concierge & Room Service')
@section('page_title', 'In-Residence Concierge & Room Service')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-black text-slate-800">Request Concierge or Room Service</h2>
        <p class="text-slate-500 text-sm mt-1">Order food delivery, laundry, extra housekeeping, or airport transfers directly to your residence.</p>
    </div>

    <form action="{{ route('customer.room-service.store') }}" method="POST" class="space-y-6" id="concierge-form">
        @csrf

        <!-- Reservation Selection -->
        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
            <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center gap-2">
                <i class="fas fa-building text-accent"></i>
                Active Reservation
            </h3>
            
            <select name="reservation_id" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent text-sm">
                <option value="">Select your active stay</option>
                @foreach($reservations as $reservation)
                    <option value="{{ $reservation->id }}">
                        {{ $reservation->property->name ?? 'Property' }} (Room/Unit: {{ $reservation->unit->unit_number ?? 'Main' }}) — Check-out: {{ $reservation->check_out->format('M j, Y') }}
                    </option>
                @endforeach
            </select>
            @if($reservations->isEmpty())
                <p class="text-sm text-red-500 mt-2">You don't have any active checked-in reservations. In-residence service is available during your stay.</p>
            @endif
        </div>

        <!-- Service Category -->
        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
            <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center gap-2">
                <i class="fas fa-concierge-bell text-accent"></i>
                Service Category
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($orderTypes as $key => $label)
                <label class="cursor-pointer">
                    <input type="radio" name="order_type" value="{{ $key }}" class="peer sr-only" {{ $loop->first ? 'checked' : '' }} onchange="onOrderTypeChange('{{ $key }}')">
                    <div class="px-3 py-3 border border-gray-200 rounded-xl text-center peer-checked:border-accent peer-checked:bg-red-50 peer-checked:text-accent hover:bg-gray-50 transition">
                        <i class="fas {{ $key === 'food' ? 'fa-utensils' : ($key === 'laundry' ? 'fa-tshirt' : ($key === 'airport_pickup' ? 'fa-plane-arrival' : 'fa-broom')) }} text-lg mb-1.5 block"></i>
                        <p class="text-xs font-bold">{{ $label }}</p>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Airport Pickup Details (conditional) -->
        <div id="airport-fields" class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm hidden">
            <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center gap-2">
                <i class="fas fa-plane-arrival text-primary"></i>
                Airport Transfer Details
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Pickup Location / Terminal</label>
                    <input type="text" name="pickup_location" placeholder="e.g. MMIA Terminal 2"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Pickup Date & Time</label>
                    <input type="datetime-local" name="pickup_time"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Flight Number</label>
                    <input type="text" name="flight_number" placeholder="e.g. BA075, QR1408"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary">
                </div>
            </div>
        </div>

        <!-- Preset Quick Picks -->
        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-sparkles text-amber-500"></i>
                    Popular Options
                </h3>
                <span class="text-xs text-gray-400">Click to add to your order</span>
            </div>
            <div id="preset-container" class="flex flex-wrap gap-2">
                <!-- Dynamically populated from JS based on selected category -->
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
            <h3 class="text-base font-bold text-gray-800 mb-2 flex items-center gap-2">
                <i class="fas fa-list-check text-accent"></i>
                Selected Items
            </h3>
            <p class="text-xs text-gray-500 mb-4">You can adjust quantities or enter custom requests.</p>
            
            <div id="items-container" class="space-y-3">
                <div class="item-row flex gap-3 items-start">
                    <div class="flex-1">
                        <input type="text" name="items[0][name]" placeholder="Item or service name" required
                            class="item-name w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-accent"
                            oninput="recalcTotal()">
                    </div>
                    <div class="w-24">
                        <input type="number" name="items[0][quantity]" value="1" min="1" max="50" placeholder="Qty"
                            class="item-qty w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-accent"
                            oninput="recalcTotal()">
                    </div>
                    <div class="w-32">
                        <input type="number" name="items[0][price]" value="0" min="0" step="100" placeholder="Price (₦)"
                            class="item-price w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-accent"
                            oninput="recalcTotal()">
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100">
                <button type="button" onclick="addItem()" class="text-xs text-accent font-bold hover:underline flex items-center gap-1">
                    <i class="fas fa-plus"></i> Add Custom Item
                </button>
                <div class="text-right">
                    <span class="text-xs text-gray-500 uppercase font-semibold">Estimated Total: </span>
                    <span id="estimated-total" class="text-base font-black text-primary">₦0.00</span>
                </div>
            </div>
        </div>

        <!-- Payment Method -->
        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
            <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center gap-2">
                <i class="fas fa-credit-card text-primary"></i>
                Payment Method
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" name="payment_method" value="folio" class="peer sr-only" checked>
                    <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-primary peer-checked:bg-blue-50/50 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-sm text-gray-800">Charge to Folio</span>
                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 bg-blue-100 text-blue-700 rounded">Recommended</span>
                        </div>
                        <p class="text-xs text-gray-500">Pay on your room folio at checkout or settle on final invoice.</p>
                    </div>
                </label>
                
                <label class="cursor-pointer">
                    <input type="radio" name="payment_method" value="paystack" class="peer sr-only">
                    <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-primary peer-checked:bg-blue-50/50 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-sm text-gray-800">Pay with Paystack</span>
                            <i class="fas fa-lock text-green-600 text-xs"></i>
                        </div>
                        <p class="text-xs text-gray-500">Card, USSD, or Bank Transfer online payment link.</p>
                    </div>
                </label>

                <label class="cursor-pointer">
                    <input type="radio" name="payment_method" value="cash" class="peer sr-only">
                    <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-primary peer-checked:bg-blue-50/50 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-sm text-gray-800">Cash on Delivery</span>
                            <i class="fas fa-money-bill-wave text-gray-400 text-xs"></i>
                        </div>
                        <p class="text-xs text-gray-500">Hand cash directly to concierge / delivery staff.</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Special Instructions -->
        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
            <h3 class="text-base font-bold text-gray-800 mb-2 flex items-center gap-2">
                <i class="fas fa-comment-alt text-accent"></i>
                Special Instructions
            </h3>
            
            <textarea name="special_instructions" rows="2" placeholder="Dietary allergies, gate directions, preferred drop-off time..."
                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-accent focus:border-transparent"></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('customer.room-service.index') }}" class="px-6 py-2.5 border border-gray-200 text-gray-700 font-semibold text-sm rounded-lg hover:bg-gray-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-2.5 bg-primary text-white font-bold text-sm rounded-lg hover:bg-primary-light transition shadow-md" {{ $reservations->isEmpty() ? 'disabled' : '' }}>
                <i class="fas fa-paper-plane mr-2"></i> Submit Request
            </button>
        </div>
    </form>
</div>

<script>
const PRESET_CATALOG = @json($presetCatalog);
let itemCount = 1;

function onOrderTypeChange(type) {
    const airportFields = document.getElementById('airport-fields');
    if (type === 'airport_pickup') {
        airportFields.classList.remove('hidden');
    } else {
        airportFields.classList.add('hidden');
    }

    renderPresets(type);
}

function renderPresets(type) {
    const container = document.getElementById('preset-container');
    container.innerHTML = '';
    const items = PRESET_CATALOG[type] || [];

    if (items.length === 0) {
        container.innerHTML = '<span class="text-xs text-gray-400 italic">No pre-configured packages for this type. Enter items below.</span>';
        return;
    }

    items.forEach(item => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'inline-flex items-center gap-2 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold rounded-lg transition border border-slate-200';
        btn.innerHTML = `<span>+ ${item.name}</span> <span class="text-slate-500 font-normal">₦${Number(item.price).toLocaleString()}</span>`;
        btn.onclick = () => addPresetItem(item.name, item.price);
        container.appendChild(btn);
    });
}

function addPresetItem(name, price) {
    const container = document.getElementById('items-container');
    const firstRow = container.querySelector('.item-row');
    const firstName = firstRow ? firstRow.querySelector('.item-name') : null;

    if (firstRow && firstName && firstName.value.trim() === '') {
        firstName.value = name;
        firstRow.querySelector('.item-price').value = price;
        recalcTotal();
        return;
    }

    const html = `
        <div class="item-row flex gap-3 items-start">
            <div class="flex-1">
                <input type="text" name="items[${itemCount}][name]" value="${name}" required
                    class="item-name w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-accent"
                    oninput="recalcTotal()">
            </div>
            <div class="w-24">
                <input type="number" name="items[${itemCount}][quantity]" value="1" min="1" max="50"
                    class="item-qty w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-accent"
                    oninput="recalcTotal()">
            </div>
            <div class="w-32">
                <input type="number" name="items[${itemCount}][price]" value="${price}" min="0" step="100"
                    class="item-price w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-accent"
                    oninput="recalcTotal()">
            </div>
            <button type="button" onclick="removeItem(this)" class="text-red-500 p-2.5 hover:text-red-700">
                <i class="fas fa-trash text-sm"></i>
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    itemCount++;
    recalcTotal();
}

function addItem() {
    const container = document.getElementById('items-container');
    const html = `
        <div class="item-row flex gap-3 items-start">
            <div class="flex-1">
                <input type="text" name="items[${itemCount}][name]" placeholder="Item or service name" required
                    class="item-name w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-accent"
                    oninput="recalcTotal()">
            </div>
            <div class="w-24">
                <input type="number" name="items[${itemCount}][quantity]" value="1" min="1" max="50"
                    class="item-qty w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-accent"
                    oninput="recalcTotal()">
            </div>
            <div class="w-32">
                <input type="number" name="items[${itemCount}][price]" value="0" min="0" step="100"
                    class="item-price w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-accent"
                    oninput="recalcTotal()">
            </div>
            <button type="button" onclick="removeItem(this)" class="text-red-500 p-2.5 hover:text-red-700">
                <i class="fas fa-trash text-sm"></i>
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    itemCount++;
    recalcTotal();
}

function removeItem(btn) {
    btn.closest('.item-row').remove();
    recalcTotal();
}

function recalcTotal() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
        const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
        total += (qty * price);
    });
    document.getElementById('estimated-total').textContent = '₦' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Initialise presets for the first checked radio
document.addEventListener('DOMContentLoaded', () => {
    const checked = document.querySelector('input[name="order_type"]:checked');
    if (checked) {
        onOrderTypeChange(checked.value);
    }
});
</script>
@endsection
