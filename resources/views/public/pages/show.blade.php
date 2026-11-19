@extends('layouts.app')

@section('title', $page->meta_title ?? ($page->title . ' - BookTala'))
@section('meta_description', $page->meta_description ?? 'Read our policy and information details on BookTala.')

@php
    $policyLinks = [
        ['slug' => 'terms-and-conditions', 'title' => 'Terms & Conditions', 'icon' => 'fa-file-contract'],
        ['slug' => 'privacy-policy', 'title' => 'Privacy Policy', 'icon' => 'fa-user-shield'],
        ['slug' => 'cancellation-policy', 'title' => 'Cancellation & Refunds', 'icon' => 'fa-undo-alt'],
        ['slug' => 'house-rules', 'title' => 'House Rules', 'icon' => 'fa-home'],
        ['slug' => 'security-deposit-policy', 'title' => 'Security Deposit Policy', 'icon' => 'fa-shield-alt'],
        ['slug' => 'faq', 'title' => 'Frequently Asked Questions', 'icon' => 'fa-question-circle'],
        ['slug' => 'owner-partnership-terms', 'title' => 'Property Owner Terms', 'icon' => 'fa-handshake'],
    ];
@endphp

@section('content')
<!-- Page Header Banner -->
<section class="bg-gradient-to-r from-slate-900 via-primary to-slate-900 text-white py-12 md:py-16">
    <div class="container mx-auto px-4 md:px-6 max-w-6xl">
        <nav class="flex items-center text-xs md:text-sm text-slate-300 mb-4 space-x-2">
            <a href="{{ route('home') }}" class="hover:text-white transition">Home</a>
            <span>/</span>
            <span class="text-accent font-medium">{{ $page->title }}</span>
        </nav>
        <h1 class="text-3xl md:text-5xl font-black tracking-tight text-white">{{ $page->title }}</h1>
        @if($page->meta_description)
            <p class="mt-3 text-slate-300 text-sm md:text-base max-w-3xl leading-relaxed">{{ $page->meta_description }}</p>
        @endif
        <div class="mt-6 flex flex-wrap items-center gap-4 text-xs text-slate-400">
            <span class="inline-flex items-center gap-1.5 bg-white/10 px-3 py-1 rounded-full text-slate-200">
                <i class="far fa-clock text-accent"></i> Last updated: {{ $page->updated_at->format('F j, Y') }}
            </span>
            <span class="inline-flex items-center gap-1.5 bg-white/10 px-3 py-1 rounded-full text-slate-200">
                <i class="fas fa-check-circle text-emerald-400"></i> Official Verified Policy
            </span>
        </div>
    </div>
</section>

<!-- Page Content & Policy Quick Navigation -->
<section class="py-12 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 md:px-6 max-w-6xl">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Sidebar: Policy Hub Navigation -->
            <aside class="lg:col-span-4 order-2 lg:order-1 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sticky top-24">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="fas fa-compass text-primary"></i> Information & Policies
                    </h3>
                    <nav class="space-y-1">
                        @foreach($policyLinks as $link)
                            <a href="{{ url($link['slug']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $page->slug === $link['slug'] ? 'bg-primary text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100 hover:text-primary' }}">
                                <i class="fas {{ $link['icon'] }} w-5 text-xs text-center {{ $page->slug === $link['slug'] ? 'text-accent' : 'text-gray-400' }}"></i>
                                <span>{{ $link['title'] }}</span>
                            </a>
                        @endforeach
                    </nav>

                    <!-- Concierge Help Box -->
                    <div class="mt-6 pt-6 border-t border-gray-100 bg-slate-50 -mx-5 -mb-5 p-5 rounded-b-xl">
                        <h4 class="text-sm font-bold text-gray-900 mb-1">Need Clarification?</h4>
                        <p class="text-xs text-gray-600 mb-3">Our 24/7 guest relations and compliance desk is always available to help.</p>
                        <a href="{{ route('pages.contact') }}" class="block w-full text-center bg-accent hover:bg-accent/90 text-white font-semibold text-xs py-2.5 px-4 rounded-lg transition-colors shadow-sm">
                            <i class="fas fa-envelope mr-1.5"></i> Contact Support
                        </a>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="lg:col-span-8 order-1 lg:order-2">
                <article class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:p-10 leading-relaxed text-gray-800 policy-content">
                    {!! $page->content !!}
                </article>

                <!-- Bottom Questions Card -->
                <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <h4 class="text-base font-bold text-gray-900">Have questions about this policy?</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Reach out to our reservations team anytime for assistance.</p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <a href="{{ route('pages.contact') }}" class="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-white text-xs font-semibold px-4 py-2.5 rounded-lg transition">
                            <i class="fas fa-envelope"></i> Message Us
                        </a>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('brand.phone')) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2.5 rounded-lg transition">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</section>

@push('styles')
<style>
    .policy-content h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f172a;
        margin-top: 2rem;
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .policy-content h2:first-of-type {
        margin-top: 0;
    }
    .policy-content h3 {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e293b;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
    }
    .policy-content p {
        margin-bottom: 1rem;
        color: #334155;
        font-size: 0.95rem;
        line-height: 1.7;
    }
    .policy-content ul, .policy-content ol {
        margin-bottom: 1.25rem;
        padding-left: 1.5rem;
        color: #334155;
        font-size: 0.95rem;
        line-height: 1.7;
    }
    .policy-content ul {
        list-style-type: disc;
    }
    .policy-content ol {
        list-style-type: decimal;
    }
    .policy-content li {
        margin-bottom: 0.5rem;
    }
    .policy-content a {
        color: #0B2D5B;
        text-decoration: underline;
        font-weight: 600;
    }
    .policy-content a:hover {
        color: #D62828;
    }
    .policy-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 1.5rem 0;
        font-size: 0.9rem;
    }
    .policy-content table th, .policy-content table td {
        padding: 10px 14px;
        border: 1px solid #e2e8f0;
    }
    .policy-content table th {
        background-color: #f8fafc;
        font-weight: 700;
        color: #1e293b;
    }
</style>
@endpush
@endsection
