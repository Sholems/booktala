@extends('layouts.app')

@section('title', 'Privacy Policy - BookTala')
@section('meta_description', 'Learn how BookTala collects, uses, and protects your personal data.')

@section('content')
<section class="py-10 md:py-14 bg-background">
    <div class="container mx-auto px-4 md:px-6 max-w-4xl">
        <h1 class="text-3xl md:text-4xl font-bold text-primary mb-6">Privacy Policy</h1>
        <div class="bg-white border border-gray-200 rounded-xl p-6 md:p-8 space-y-4 text-gray-700">
            <p>We collect only the information needed to process bookings, provide support, and improve guest experience.</p>
            <p>Your personal data is handled securely and is not sold to third parties.</p>
            <p>Payment and sensitive details are processed through approved secure services.</p>
            <p>If you need assistance regarding your data, reach us via the <a href="{{ route('pages.contact') }}" class="text-primary hover:underline">contact page</a>.</p>
        </div>
    </div>
</section>
@endsection
