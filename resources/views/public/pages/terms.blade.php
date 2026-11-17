@extends('layouts.app')

@section('title', 'Terms and Conditions - BookTala')
@section('meta_description', 'Read the booking, payment, and stay terms for BookTala.')

@section('content')
<section class="py-10 md:py-14 bg-background">
    <div class="container mx-auto px-4 md:px-6 max-w-4xl">
        <h1 class="text-3xl md:text-4xl font-bold text-primary mb-6">Terms and Conditions</h1>
        <div class="bg-white border border-gray-200 rounded-xl p-6 md:p-8 space-y-4 text-gray-700">
            <p>By booking with BookTala, you agree to the terms governing reservations, payments, and property usage.</p>
            <p>Guests are responsible for accurate booking information and compliance with house rules.</p>
            <p>Check-in/check-out, cancellation, and refund handling are subject to your booking policy and confirmation details.</p>
            <p>For clarification, contact us through the <a href="{{ route('pages.contact') }}" class="text-primary hover:underline">contact page</a>.</p>
        </div>
    </div>
</section>
@endsection
