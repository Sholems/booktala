@extends('layouts.app')

@section('title', 'Contact Us - BookTala')
@section('meta_description', 'Contact BookTala for bookings and enquiries. Reach us by phone, email, or send a message through our contact form.')

@section('content')
<section class="relative overflow-hidden bg-primary text-white">
    <div class="absolute inset-0 bg-gradient-to-r from-primary via-primary/95 to-black/60"></div>
    <div class="relative container mx-auto px-4 md:px-6 py-16 md:py-24">
        <p class="uppercase tracking-[0.2em] text-xs md:text-sm text-accent mb-4">Contact Us</p>
        <h1 class="text-3xl md:text-5xl font-bold max-w-3xl leading-tight mb-4">We are here to help with your next stay</h1>
        <p class="text-base md:text-lg text-gray-200 max-w-2xl">
            Reach us for bookings, long-stay requests, corporate enquiries, and support.
        </p>
    </div>
</section>

<section class="py-10 md:py-14 bg-background">
    <div class="container mx-auto px-4 md:px-6">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 md:gap-8">
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="w-11 h-11 rounded-full bg-primary text-white flex items-center justify-center mb-3">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3 class="font-bold text-primary mb-1">Call Us</h3>
                    <a class="text-gray-700 hover:text-primary" href="tel:{{ preg_replace('/[^0-9+]/', '', \App\Models\Setting::get('phone', config('brand.phone'))) }}">{{ \App\Models\Setting::get('phone', config('brand.phone')) }}</a>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="w-11 h-11 rounded-full bg-primary text-white flex items-center justify-center mb-3">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3 class="font-bold text-primary mb-1">Email</h3>
                    <a class="text-gray-700 hover:text-primary break-words" href="mailto:{{ \App\Models\Setting::get('email', config('brand.email')) }}">{{ \App\Models\Setting::get('email', config('brand.email')) }}</a>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="w-11 h-11 rounded-full bg-primary text-white flex items-center justify-center mb-3">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3 class="font-bold text-primary mb-1">Address</h3>
                    <p class="text-gray-700">{{ \App\Models\Setting::get('address', config('brand.address')) }}</p>
                </div>

                <div class="bg-gradient-to-br from-primary to-[#07203F] text-white rounded-xl p-5 shadow-sm">
                    <h3 class="font-bold mb-2">Check-in &amp; Check-out</h3>
                    <p class="text-sm text-gray-200">Check-in from 2:00 PM</p>
                    <p class="text-sm text-gray-200">Check-out by 12:00 PM</p>
                </div>
            </div>

            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 md:p-8">
                    <h2 class="text-2xl font-bold text-primary mb-2">Send Us a Message</h2>
                    <p class="text-gray-600 mb-6">Fill out the form and our team will get back to you shortly.</p>

                    @if(session('success'))
                    <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                        <ul class="list-disc pl-5 text-sm">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('pages.contact.submit') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Full Name</label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Email Address</label>
                                <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Phone Number</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Subject</label>
                                <input type="text" name="subject" value="{{ old('subject') }}" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Message</label>
                            <textarea name="message" rows="6" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">{{ old('message') }}</textarea>
                        </div>

                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-accent text-white font-semibold hover:bg-accent/90 transition">
                            Send Message
                            <i class="fas fa-paper-plane text-sm"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
