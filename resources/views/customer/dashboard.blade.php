@extends('layouts.customer')

@section('page_title', 'My Dashboard')

@section('content')
<div class="container mx-auto px-4 md:px-6 py-6 md:py-8">
    <h1 class="text-2xl md:text-3xl font-bold text-primary mb-6 md:mb-8">Welcome, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') ?: auth()->user()->name }}!</h1>

    @if($currentStay)
    <section class="mb-8 rounded-xl bg-white p-5 md:p-6 shadow">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-accent">{{ $currentStay->booking_status === 'checked_in' ? 'Current stay' : 'Your next stay' }}</p>
                <h2 class="text-xl font-bold text-gray-900">{{ $currentStay->property->name ?? 'Apartment' }}</h2>
                <p class="text-sm text-gray-600">{{ $currentStay->check_in->format('D, j M') }} – {{ $currentStay->check_out->format('D, j M Y') }} · {{ $currentStay->guests }} {{ \Illuminate\Support\Str::plural('guest', $currentStay->guests) }} · {{ $currentStay->booking_ref }}</p>
            </div>
            <a href="{{ route('customer.reservations.show', $currentStay) }}" class="text-sm text-primary hover:underline">View booking</a>
        </div>
        @include('customer.partials.journey', ['reservation' => $currentStay])
    </section>
    @endif

    @foreach($needsAction as $reservation)
    @php $next = \App\Support\GuestJourney::next($reservation); @endphp
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <p class="text-sm"><strong>{{ $next['title'] }}</strong> · {{ $reservation->property->name ?? '' }}, {{ $reservation->check_in->format('j M') }} ({{ $reservation->booking_ref }})</p>
        <a href="{{ $next['url'] ?? route('customer.reservations.show', $reservation) }}" class="text-sm font-semibold text-primary hover:underline">{{ $next['action'] ?? 'View booking' }} &rarr;</a>
    </div>
    @endforeach

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
        <a href="{{ route('customer.reservations.index') }}" class="block bg-white p-4 md:p-6 rounded-lg shadow hover:shadow-md transition">
            <p class="text-3xl font-bold text-primary">{{ $upcomingCount }}</p>
            <h3 class="font-bold">Upcoming {{ \Illuminate\Support\Str::plural('stay', $upcomingCount) }}</h3>
            <p class="text-gray-600 text-sm">{{ $reservations->count() }} {{ \Illuminate\Support\Str::plural('booking', $reservations->count()) }} in total</p>
        </a>

        <a href="{{ route('properties.index') }}" class="block bg-white p-4 md:p-6 rounded-lg shadow hover:shadow-md transition">
            <p class="text-3xl text-primary"><i class="fas fa-building"></i></p>
            <h3 class="font-bold">Browse apartments</h3>
            <p class="text-gray-600 text-sm">Find your next stay</p>
        </a>

        <a href="{{ route('customer.wishlist.index') }}" class="block bg-white p-4 md:p-6 rounded-lg shadow hover:shadow-md transition">
            <p class="text-3xl font-bold text-primary">{{ $wishlists->count() }}</p>
            <h3 class="font-bold">Saved apartments</h3>
            <p class="text-gray-600 text-sm">Your wishlist</p>
        </a>
    </div>

    @if(!$currentStay && $reservations->isEmpty())
    <div class="mt-8 rounded-xl bg-white p-8 text-center shadow">
        <p class="text-gray-600">You have no bookings yet.</p>
        <a href="{{ route('properties.index') }}" class="mt-4 inline-block rounded-lg bg-accent px-6 py-3 font-semibold text-white hover:bg-accent/90">Find an apartment</a>
    </div>
    @endif
</div>
@endsection
