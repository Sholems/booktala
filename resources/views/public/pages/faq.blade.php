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
    ['How do I book an apartment with BookTala?', 'Browse our verified luxury shortlets across Lagos, choose your check-in and check-out dates, select guest count, and pay securely online. You will receive an instant official booking confirmation and digital receipt via email.'],
    ['What payment methods are supported?', 'We accept online payments via debit/credit cards (Visa, Mastercard, Verve), direct Nigerian bank transfers, and USSD via our secured Paystack gateway. Front-desk walk-in payments via POS or bank transfer are also supported.'],
    ['What are the check-in and check-out times?', "Standard check-in begins at {$checkIn} (2:00 PM) and check-out is strictly by {$checkOut} (11:00 AM/12:00 PM) to allow our professional housekeeping team to sanitize the residence. Early check-in or late check-out is available upon request."],
    ['Is 24/7 electricity guaranteed in BookTala apartments?', 'Yes. All BookTala residences operate dual industrial standby diesel generators and solar inverter systems ensuring uninterrupted 24/7 power supply, air conditioning, and high-speed fiber WiFi throughout your entire stay.'],
    ['Can I check in online before arrival?', 'Yes. Our digital guest portal allows pre-arrival online check-in where you can upload your government-issued ID and expected arrival time, enabling contactless front-desk key pickup in under 60 seconds.'],
    ['What documents are required at check-in?', 'A valid government-issued photo ID (National ID card, Driver License, Voter Card, or International Passport) matching your reservation details. The primary guest must be 18 years or older.'],
    ['How does the refundable caution fee work?', 'Certain apartments require a refundable security caution deposit to cover accidental damages. Caution deposits are refunded back to your source account within 24–48 hours post check-out inspection.'],
    ['Can I request on-demand services during my stay?', 'Yes. Guests can order room service, fresh towels, extra linens, gourmet private chef meals, laundry services, or airport transfers directly through the in-stay concierge portal or 24/7 WhatsApp concierge.'],
    ['What is the cancellation and refund policy?', 'Each apartment displays its cancellation policy prior to booking. Standard reservations qualify for full or partial refunds depending on notice period. View our full ' . $link(route('pages.cancellation'), 'cancellation policy') . ' for detailed timelines.'],
    ['Do you offer weekly or monthly corporate stay discounts?', 'Yes. BookTala provides automated dynamic discounts for extended stays of 7+ nights (weekly discount) and 30+ nights (monthly discount). Custom corporate packages with dedicated account managers are available.'],
    ['Who owns and manages BookTala apartments?', 'BookTala is the flagship residential hospitality brand operated by ' . e(config('brand.owner', 'G-Briel Property Ltd')) . ', a registered Nigerian real estate and property management enterprise.'],
    ['How do I contact BookTala concierge?', 'Call our 24/7 hotline at ' . $link('tel:' . preg_replace('/[^0-9+]/', '', $phone), $phone) . ', email ' . $link('mailto:' . $email, $email) . ', or visit our ' . $link(route('pages.contact'), 'contact page') . ' for immediate assistance.'],
];
@endphp

@push('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    @foreach($faqs as [$q, $a])
    {
      "@type": "Question",
      "name": {{ json_encode($q) }},
      "acceptedAnswer": {
        "@type": "Answer",
        "text": {{ json_encode(strip_tags($a)) }}
      }
    }@if(!$loop->last),@endif
    @endforeach
  ]
}
</script>
@endpush

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
