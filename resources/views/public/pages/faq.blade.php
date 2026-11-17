@extends('layouts.app')

@section('title', 'FAQ - BookTala')
@section('meta_description', 'Answers to common questions about booking, paying, checking in and cancelling with BookTala.')

@php
$phone = \App\Models\Setting::get('phone', config('brand.phone'));
$email = \App\Models\Setting::get('email', config('brand.email'));
$checkIn = date('g:i A', strtotime(\App\Models\Setting::get('check_in_time', '14:00')));
$checkOut = date('g:i A', strtotime(\App\Models\Setting::get('check_out_time', '12:00')));
$link = fn ($href, $text) => '<a href="' . e($href) . '" class="text-primary hover:underline">' . e($text) . '</a>';
$faqs = [
    ['How do I book an apartment?', 'Browse our apartments, choose your dates and pay securely online. You get a confirmation by email straight away.'],
    ['How can I pay?', 'Online by card, bank transfer or USSD through Paystack. Your booking is confirmed once payment is received.'],
    ['What are the check-in and check-out times?', "Check-in is from {$checkIn} and check-out is by {$checkOut}. Early check-in or late check-out may be possible on request."],
    ['Can I check in before I arrive?', 'Yes. Use online check-in from your account to send your ID and arrival details, so check-in at the front desk takes a minute.'],
    ['What do I need at check-in?', 'A valid government-issued ID or passport matching the details you gave. Guests must be 18 or older to book.'],
    ['Is there a caution fee?', 'Some apartments require a refundable caution fee. If one applies, it is shown before you pay.'],
    ['Can I request anything during my stay?', 'Yes. Request towels, toiletries, laundry and more from your account, and follow each request until it is delivered.'],
    ['What is the cancellation policy?', 'Each apartment shows its policy before you book, and you can cancel online before your check-in date. Read the full ' . $link(route('pages.cancellation'), 'cancellation policy') . '.'],
    ['Do you offer longer stays?', 'Yes. Weekly and monthly stays are welcome; contact us for longer-stay rates.'],
    ['Are smoking and pets allowed?', 'Smoking is not allowed inside the apartments, and pets need prior written approval.'],
    ['Who runs BookTala?', 'BookTala is a brand of ' . e(config('brand.owner')) . '.'],
    ['How can I contact you?', 'Call ' . $link('tel:' . preg_replace('/[^0-9+]/', '', $phone), $phone) . ', email ' . $link('mailto:' . $email, $email) . ', or use our ' . $link(route('pages.contact'), 'contact page') . '.'],
];
@endphp

@section('content')
<section class="py-10 md:py-14 bg-background">
    <div class="container mx-auto px-4 md:px-6 max-w-4xl">
        <h1 class="text-3xl md:text-4xl font-bold text-primary mb-8">Frequently Asked Questions</h1>

        <div class="space-y-4">
            @foreach($faqs as [$question, $answer])
            <details class="bg-white rounded-xl border border-gray-200 p-5" @if($loop->first) open @endif>
                <summary class="font-semibold text-gray-900 cursor-pointer">{{ $question }}</summary>
                <p class="text-gray-700 mt-3">{!! $answer !!}</p>
            </details>
            @endforeach
        </div>
    </div>
</section>
@endsection
