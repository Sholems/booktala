@extends('layouts.customer')

@section('title', 'New Room Service Order')
@section('page_title', 'Room Service')

@section('content')
<div class="max-w-2xl mx-auto">
    <form action="{{ route('customer.room-service.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Reservation Selection -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-building text-[#E11D2E]"></i>
                Select Property
            </h3>
            
            <select name="reservation_id" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E] focus:border-transparent">
                <option value="">Select a reservation</option>
                @foreach($reservations as $reservation)
                    <option value="{{ $reservation->id }}">
                        {{ $reservation->property->name }} - {{ $reservation->check_in->format('M j') }} to {{ $reservation->check_out->format('M j, Y') }}
                    </option>
                @endforeach
            </select>
            @if($reservations->isEmpty())
                <p class="text-sm text-red-500 mt-2">You don't have any active reservations. Only checked-in guests can place orders.</p>
            @endif
        </div>

        <!-- Order Type -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-list-alt text-[#E11D2E]"></i>
                Order Type
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($orderTypes as $key => $label)
                <label class="cursor-pointer">
                    <input type="radio" name="order_type" value="{{ $key }}" class="peer sr-only" required>
                    <div class="px-4 py-3 border border-gray-200 rounded-lg text-center peer-checked:border-[#E11D2E] peer-checked:bg-[#E11D2E]/10 peer-checked:text-[#E11D2E] hover:bg-gray-50 transition">
                        <i class="fas {{ $key === 'food' ? 'fa-utensils' : ($key === 'laundry' ? 'fa-tshirt' : ($key === 'amenity' ? 'fa-spa' : ($key === 'maintenance' ? 'fa-wrench' : 'fa-box'))) }} mb-1"></i>
                        <p class="text-sm font-medium">{{ $label }}</p>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Items -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-utensils text-[#E11D2E]"></i>
                Order Items
            </h3>
            <p class="-mt-2 mb-4 text-sm text-gray-500">List what you need. Our team will confirm any charges when they accept your request.</p>
            
            <div id="items-container" class="space-y-3">
                <div class="item-row flex gap-3 items-start">
                    <div class="flex-1">
                        <input type="text" name="items[0][name]" placeholder="Item name" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E] focus:border-transparent">
                    </div>
                    <div class="w-20">
                        <input type="number" name="items[0][quantity]" value="1" min="1" placeholder="Qty"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E] focus:border-transparent">
                    </div>
                </div>
            </div>
            
            <button type="button" onclick="addItem()" class="mt-4 text-sm text-[#E11D2E] font-medium hover:underline">
                <i class="fas fa-plus mr-1"></i> Add Another Item
            </button>
        </div>

        <!-- Special Instructions -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-comment-alt text-[#E11D2E]"></i>
                Special Instructions
            </h3>
            
            <textarea name="special_instructions" rows="3" placeholder="Any special requests or instructions..."
                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E] focus:border-transparent"></textarea>
        </div>

        <div class="flex justify-end gap-4">
            <a href="{{ route('customer.room-service.index') }}" class="px-6 py-3 border border-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 bg-[#0B2D5B] text-white font-bold rounded-lg hover:bg-[#123E7A] transition" {{ $reservations->isEmpty() ? 'disabled' : '' }}>
                <i class="fas fa-shopping-cart mr-2"></i>
                Place Order
            </button>
        </div>
    </form>
</div>

<script>
let itemCount = 1;
function addItem() {
    const container = document.getElementById('items-container');
    const html = `
        <div class="item-row flex gap-3 items-start">
            <div class="flex-1">
                <input type="text" name="items[${itemCount}][name]" placeholder="Item name" required
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E] focus:border-transparent">
            </div>
            <div class="w-20">
                <input type="number" name="items[${itemCount}][quantity]" value="1" min="1" placeholder="Qty"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E] focus:border-transparent">
            </div>
            <button type="button" onclick="this.closest('.item-row').remove()" class="text-red-500 p-3 hover:text-red-700">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    itemCount++;
}
</script>
@endsection