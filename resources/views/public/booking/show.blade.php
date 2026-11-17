@extends('layouts.app')

@section('title', 'Book ' . $property->title . ' - BookTala')

@push('styles')
<style>
    .booking-summary { background: #f9fafb; }
</style>
@endpush

@section('content')
<section class="py-8">
    <div class="container mx-auto px-6">
        <nav class="text-sm text-gray-600 mb-4">
            <a href="{{ route('home') }}" class="hover:text-primary">Home</a>
            <span class="mx-2">/</span>
            <a href="{{ route('properties.show', $property->slug) }}" class="hover:text-primary">{{ $property->title }}</a>
            <span class="mx-2">/</span>
            <span class="text-primary">Book Now</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                @guest
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-bold mb-4">Guest Booking</h2>
                    <p class="text-gray-600 mb-4">Enter your details to complete the booking. We'll create an account for you.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Full Name *</label>
                            <input type="text" name="guest_name" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Email Address *</label>
                            <input type="email" name="guest_email" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Phone Number *</label>
                            <input type="text" name="guest_phone" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Password * <span class="text-gray-500 font-normal">(new account, or your existing one)</span></label>
                            <input type="password" name="guest_password" class="w-full border rounded px-3 py-2" required>
                        </div>
                    </div>
                </div>
                @endguest

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">Booking Details</h2>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Check-in Date *</label>
                        <input type="date" name="check_in" value="{{ request('check_in') }}" class="w-full border rounded px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Check-out Date *</label>
                        <input type="date" name="check_out" value="{{ request('check_out') }}" class="w-full border rounded px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Number of Guests *</label>
                        <select name="guests" class="w-full border rounded px-3 py-2" required>
                            @for($i = 1; $i <= $property->max_guests; $i++)
                            <option value="{{ $i }}" {{ request('guests') == $i ? 'selected' : '' }}>{{ $i }} Guest{{ $i > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Discount Code</label>
                        <input type="text" name="discount_code" class="w-full border rounded px-3 py-2" placeholder="Enter code if available">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Special Requests</label>
                        <textarea name="special_requests" class="w-full border rounded px-3 py-2" rows="3" placeholder="Any special requests..."></textarea>
                    </div>

                    <button type="button" id="proceed-to-payment" class="w-full bg-accent text-white py-3 rounded-lg font-bold hover:bg-accent/90">
                        Proceed to Payment
                    </button>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                    <h3 class="text-lg font-bold mb-4">{{ $property->title }}</h3>
                    <p class="text-gray-600 text-sm mb-4"><i class="fas fa-map-marker-alt mr-2"></i>{{ $property->city }}, {{ $property->state }}</p>
                    
                    @if($property->images->first())
                    <img src="{{ $property->images->first()->image_path }}" class="w-full h-32 object-cover rounded mb-4">
                    @endif
                    
                    <div class="border-t pt-4 mt-4">
                        <div class="flex justify-between py-2">
                            <span>₦{{ number_format($property->price_per_night) }} x {{ $pricing['nights'] ?? 0 }} nights</span>
                            <span>₦{{ number_format($pricing['subtotal'] ?? 0) }}</span>
                        </div>
                        @if(($pricing['discount_amount'] ?? 0) > 0)
                        <div class="flex justify-between py-2 text-green-600">
                            <span>Discount</span>
                            <span>-₦{{ number_format($pricing['discount_amount']) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between py-2">
                            <span>VAT (7.5%)</span>
                            <span>₦{{ number_format($pricing['tax_amount'] ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-t font-bold text-lg">
                            <span>Total</span>
                            <span class="text-primary">₦{{ number_format($pricing['total_amount'] ?? $property->price_per_night) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
document.getElementById('proceed-to-payment').addEventListener('click', function() {
    const form = new FormData();
    form.append('property_id', '{{ $property->id }}');
    form.append('check_in', document.querySelector('input[name="check_in"]').value);
    form.append('check_out', document.querySelector('input[name="check_out"]').value);
    form.append('guests', document.querySelector('select[name="guests"]').value);
    form.append('discount_code', document.querySelector('input[name="discount_code"]').value || '');
    form.append('special_requests', document.querySelector('textarea[name="special_requests"]').value || '');
    
    @if(!auth()->check())
    form.append('guest_name', document.querySelector('input[name="guest_name"]').value);
    form.append('guest_email', document.querySelector('input[name="guest_email"]').value);
    form.append('guest_phone', document.querySelector('input[name="guest_phone"]').value);
    form.append('guest_password', document.querySelector('input[name="guest_password"]').value);
    @endif

    fetch('{{ route("booking.process") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: form
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.authorization_url;
        } else {
            alert(data.message || 'An error occurred');
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
    });
});
</script>
@endpush
@endsection
