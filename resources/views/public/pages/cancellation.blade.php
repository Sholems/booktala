@extends('layouts.app')

@section('title', 'Cancellation Policy - BookTala')
@section('meta_description', 'Understand cancellation timelines and refund eligibility for BookTala reservations.')

@section('content')
<section class="py-10 md:py-14 bg-background">
    <div class="container mx-auto px-4 md:px-6 max-w-4xl">
        <h1 class="text-3xl md:text-4xl font-bold text-primary mb-6">Cancellation Policy</h1>
        <div class="bg-white border border-gray-200 rounded-xl p-6 md:p-8 space-y-4 text-gray-700">
            <p>Cancellation and refund eligibility depend on booking timing, stay dates, and applicable reservation terms.</p>
            <p>Where eligible, refunds are processed to the original payment method according to provider timelines.</p>
            <p>For urgent changes, contact support as early as possible to explore available options.</p>
            <p>Need help? Visit the <a href="{{ route('pages.contact') }}" class="text-primary hover:underline">contact page</a>.</p>
        </div>
    </div>
</section>
@endsection
