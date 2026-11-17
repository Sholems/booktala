@extends('layouts.app')

@section('title', 'Booking Confirmed - BookTala')

@section('content')
<section class="py-12 bg-primary text-white">
    <div class="container mx-auto px-6 text-center">
        <div class="flex justify-center mb-4">
            <i class="fas fa-check-circle text-6xl text-green-400"></i>
        </div>
        <h1 class="text-4xl font-bold mb-2">Booking Confirmed!</h1>
        <p class="text-xl">Your reservation has been successfully confirmed</p>
    </div>
</section>

<section class="py-12">
    <div class="container mx-auto px-6 max-w-3xl">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <p class="text-gray-600">Booking Reference</p>
                <p class="text-2xl font-bold text-primary">{{ $reservation->booking_ref }}</p>
            </div>

            <div class="border-b pb-6 mb-6">
                <h2 class="text-xl font-bold mb-4">Property Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 text-sm">Property</p>
                        <p class="font-semibold">{{ $reservation->property->title }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Location</p>
                        <p class="font-semibold">{{ $reservation->property->city }}, {{ $reservation->property->state }}</p>
                    </div>
                </div>
            </div>

            <div class="border-b pb-6 mb-6">
                <h2 class="text-xl font-bold mb-4">Stay Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 text-sm">Check-in</p>
                        <p class="font-semibold">{{ $reservation->check_in->format('l, F d, Y') }}</p>
                        <p class="text-sm text-gray-500">From {{ $reservation->property->check_in_time ?? '2:00 PM' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Check-out</p>
                        <p class="font-semibold">{{ $reservation->check_out->format('l, F d, Y') }}</p>
                        <p class="text-sm text-gray-500">By {{ $reservation->property->check_out_time ?? '11:00 AM' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Number of Guests</p>
                        <p class="font-semibold">{{ $reservation->guests }} Guest{{ $reservation->guests > 1 ? 's' : '' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Duration</p>
                        <p class="font-semibold">{{ $reservation->nights }} Night{{ $reservation->nights > 1 ? 's' : '' }}</p>
                    </div>
                </div>
            </div>

            <div class="border-b pb-6 mb-6">
                <h2 class="text-xl font-bold mb-4">Guest Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 text-sm">Name</p>
                        <p class="font-semibold">{{ $reservation->user->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Email</p>
                        <p class="font-semibold">{{ $reservation->user->email }}</p>
                    </div>
                </div>
            </div>

            <div class="pb-6 mb-6">
                <h2 class="text-xl font-bold mb-4">Payment Summary</h2>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span>₦{{ number_format($reservation->subtotal) }}</span>
                    </div>
                    @if($reservation->discount_amount > 0)
                    <div class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span>-₦{{ number_format($reservation->discount_amount) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-600">VAT</span>
                        <span>₦{{ number_format($reservation->tax_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t">
                        <span>Total Paid</span>
                        <span class="text-primary">₦{{ number_format($reservation->total_amount) }}</span>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="{{ route('customer.reservations.show', $reservation) }}" class="inline-block bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary/90">
                    View My Reservation
                </a>
                <a href="{{ route('home') }}" class="inline-block ml-4 text-primary hover:underline">
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
