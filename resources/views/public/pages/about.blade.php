@extends('layouts.app')

@section('title', 'About Us - BookTala')
@section('meta_description', 'BookTala, a brand of G-Briel Property Ltd, makes short-let stays simple to find, book and enjoy.')

@php
    $owner = config('brand.owner');
    $photos = [
        'living' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80',
        'bedroom' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=80',
        'kitchen' => 'https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=1200&q=80',
        'lounge' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80',
    ];
@endphp

@section('content')
<section class="relative overflow-hidden bg-primary text-white">
    <div class="relative container mx-auto px-4 md:px-6 py-16 md:py-24 grid lg:grid-cols-12 gap-10 items-center">
        <div class="lg:col-span-6">
            <p class="text-sm font-semibold uppercase tracking-wider text-accent">About BookTala</p>
            <h1 class="mt-3 text-3xl md:text-5xl font-bold leading-tight">Stays, travels and better living, made simple</h1>
            <p class="mt-5 text-base md:text-lg text-gray-200 leading-relaxed">{{ config('brand.description') }}</p>
            <p class="mt-6 text-sm text-gray-300">BookTala is a brand of <strong class="text-white">{{ $owner }}</strong>.</p>
        </div>
        <div class="lg:col-span-6 grid grid-cols-5 gap-4">
            <img class="col-span-3 h-72 md:h-96 w-full object-cover rounded-2xl" src="{{ $photos['living'] }}" alt="A BookTala living room">
            <img class="col-span-2 h-72 md:h-96 w-full object-cover rounded-2xl" src="{{ $photos['bedroom'] }}" alt="A BookTala bedroom">
        </div>
    </div>
</section>

<section class="py-16 md:py-20 bg-white">
    <div class="container mx-auto px-4 md:px-6 grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
        <img class="w-full h-72 md:h-[440px] object-cover rounded-2xl shadow-lg" src="{{ $photos['kitchen'] }}" alt="A fully equipped BookTala kitchen">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wider text-accent">What the name means</p>
            <h2 class="mt-2 text-3xl md:text-4xl font-bold text-primary">Book + Tala</h2>
            <dl class="mt-6 space-y-5">
                <div class="flex gap-4">
                    <dt class="shrink-0 w-12 h-12 rounded-xl bg-primary/5 text-primary flex items-center justify-center"><i class="fas fa-book-open"></i></dt>
                    <dd><strong class="block text-lg text-primary">Book</strong><span class="text-gray-600">Booking accommodation, quickly and securely online.</span></dd>
                </div>
                <div class="flex gap-4">
                    <dt class="shrink-0 w-12 h-12 rounded-xl bg-accent/10 text-accent flex items-center justify-center"><i class="fas fa-map-marker-alt"></i></dt>
                    <dd><strong class="block text-lg text-accent">Tala</strong><span class="text-gray-600">Travel, Accommodation, Lifestyle and Access.</span></dd>
                </div>
            </dl>
            <p class="mt-6 text-gray-700 leading-relaxed">Whether it's a business trip, a family holiday or a longer stay, BookTala gives you the space and privacy of a home with the comfort and service of a hotel.</p>
        </div>
    </div>
</section>

<section class="py-16 md:py-20 bg-background">
    <div class="container mx-auto px-4 md:px-6">
        <div class="max-w-2xl mb-10">
            <p class="text-sm font-semibold uppercase tracking-wider text-accent">Our promise</p>
            <h2 class="mt-2 text-3xl md:text-4xl font-bold text-primary">What every BookTala stay includes</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-5">
            <article class="rounded-2xl bg-white p-7 shadow-sm ring-1 ring-gray-100">
                <i class="fas fa-shield-alt text-2xl text-primary"></i>
                <h3 class="mt-4 text-xl font-bold text-primary">Trust</h3>
                <p class="mt-2 text-gray-600 leading-relaxed">Verified apartments, secure online payment and clear policies, so you know exactly what you're booking.</p>
            </article>
            <article class="rounded-2xl bg-white p-7 shadow-sm ring-1 ring-gray-100">
                <i class="fas fa-couch text-2xl text-primary"></i>
                <h3 class="mt-4 text-xl font-bold text-primary">Comfort</h3>
                <p class="mt-2 text-gray-600 leading-relaxed">Fully furnished homes with kitchens, living space and private bedrooms, cleaned and prepared before you arrive.</p>
            </article>
            <article class="rounded-2xl bg-white p-7 shadow-sm ring-1 ring-gray-100">
                <i class="fas fa-concierge-bell text-2xl text-primary"></i>
                <h3 class="mt-4 text-xl font-bold text-primary">Service</h3>
                <p class="mt-2 text-gray-600 leading-relaxed">Check in online, request what you need during your stay and message our team any time.</p>
            </article>
        </div>
    </div>
</section>

<section class="py-16 md:py-20 bg-white">
    <div class="container mx-auto px-4 md:px-6 grid lg:grid-cols-2 gap-10 items-center">
        <div>
            <h2 class="text-3xl md:text-4xl font-bold text-primary">Ready for your next stay?</h2>
            <p class="mt-4 text-gray-700 leading-relaxed">Browse our apartments, pick your dates and book in minutes. Questions first? Our team is happy to help.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('properties.index') }}" class="inline-flex items-center gap-2 rounded-full bg-accent px-7 py-3 font-semibold text-white hover:bg-accent/90">Browse apartments <i class="fas fa-arrow-right text-sm"></i></a>
                <a href="{{ route('pages.contact') }}" class="inline-flex items-center gap-2 rounded-full border-2 border-primary px-7 py-3 font-semibold text-primary hover:bg-primary hover:text-white">Contact us</a>
            </div>
        </div>
        <img class="w-full h-72 md:h-[380px] object-cover rounded-2xl shadow-lg" src="{{ $photos['lounge'] }}" alt="A bright BookTala lounge">
    </div>
</section>
@endsection
