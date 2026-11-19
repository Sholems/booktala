@extends('layouts.app')
@php
use Illuminate\Support\Str;
$sectionsArray = $sections->keyBy('section_key');
$sectionItems = fn ($key) => isset($sectionsArray[$key]) ? (json_decode($sectionsArray[$key]->content ?? '', true) ?: []) : [];
$stats = $sectionItems('stats');
$steps = $sectionItems('how_it_works');
$pricing = $sectionItems('pricing');
$testimonials = $sectionItems('reviews');
$facilities = $sectionItems('amenities');
@endphp

@section('title', 'BookTala - Stays, Travels & Better Living')
@section('meta_description', 'BookTala by G-Briel Property Ltd: fully furnished short-let apartments with 24/7 power, WiFi, air-conditioning, parking and top-tier security.')

@section('content')
@include('public.home.01-hero')

@include('public.home.02-availability-search')

@include('public.home.03-guarantees')

@include('public.home.04-stats')

@include('public.home.05-welcome')

@push('styles')
<style>
    .hero-bg { animation: heroZoom 20s ease-out forwards; transform-origin: center; }
    @keyframes heroZoom { from { transform: scale(1.12); } to { transform: scale(1); } }
    .hero-fade { animation: heroFade .9s ease-out both; }
    .hero-fade-delay { animation-delay: .25s; }
    @keyframes heroFade { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { .hero-bg, .hero-fade { animation: none; } }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const counters = document.querySelectorAll('.stat-count');
        if (!counters.length || !('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const run = (el) => {
            const target = parseFloat(el.dataset.target);
            const start = performance.now(), duration = 1400;
            const tick = (now) => {
                const p = Math.min((now - start) / duration, 1);
                el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString();
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        };
        const io = new IntersectionObserver((entries) => entries.forEach((e) => {
            if (e.isIntersecting) { run(e.target); io.unobserve(e.target); }
        }), { threshold: 0.5 });
        counters.forEach((el) => io.observe(el));
    })();
</script>
@endpush

@include('public.home.06-how-it-works')

@include('public.home.07-featured-properties')

@include('public.home.08-airport-pickup')

@include('public.home.09-blog')

@include('public.home.10-pricing')

@include('public.home.11-amenities')

@include('public.home.12-reviews')

@include('public.home.13-owner-partnership')

@include('public.home.14-vip-club-newsletter')

@include('public.home.15-cta')
@endsection
