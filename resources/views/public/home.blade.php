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
@if(isset($sectionsArray['hero']) && $sectionsArray['hero']->is_visible)
@php
    $hero = $sectionsArray['hero'];
    $heroPhone = \App\Models\Setting::get('phone', config('brand.phone'));
    $heroTime = function ($key, $default) {
        try { return \Carbon\Carbon::parse(\App\Models\Setting::get($key, $default))->format('g:i A'); }
        catch (\Throwable $e) { return \App\Models\Setting::get($key, $default); }
    };
    $heroCheckIn = $heroTime('check_in_time', '14:00');
    $heroCheckOut = $heroTime('check_out_time', '12:00');
    $heroFromPrice = \App\Models\Property::active()->min('price_per_night');
    // Highlight the brand name in gold when the headline contains it, otherwise its last two words.
    $heroTitle = trim($hero->title ?: 'Welcome to BookTala');
    $heroBrand = \App\Models\Setting::get('site_name', config('brand.name'));
    if ($heroBrand && Str::endsWith($heroTitle, $heroBrand) && $heroTitle !== $heroBrand) {
        $heroLead = trim(Str::beforeLast($heroTitle, $heroBrand));
        $heroHighlight = $heroBrand;
    } else {
        $heroWords = explode(' ', $heroTitle);
        $heroHighlight = count($heroWords) > 2 ? implode(' ', array_splice($heroWords, -2)) : '';
        $heroLead = implode(' ', $heroWords);
    }
@endphp
<section class="hero relative overflow-hidden bg-primary text-white">
    <div class="hero-bg absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $hero->image ?? 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80' }}')"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-primary/95 via-primary/75 to-primary/20"></div>
    <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-primary/80 to-transparent"></div>
    <div class="hidden lg:block absolute -right-24 -top-24 w-96 h-96 rounded-full border border-accent/30"></div>
    <div class="hidden lg:block absolute -right-10 -top-10 w-72 h-72 rounded-full border border-accent/20"></div>

    <div class="relative z-10 container mx-auto px-4 md:px-6 pt-16 pb-24 md:pt-24 md:pb-28 lg:pt-28 lg:pb-32">
        <div class="grid lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7 text-center lg:text-left hero-fade">
                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 text-xs md:text-sm tracking-wide">
                    <i class="fas fa-map-marker-alt text-accent"></i>
                    {{ $hero->content ?: config('brand.city') }}
                </span>
                <h1 class="mt-5 text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-extrabold leading-[1.05] tracking-tight">
                    {{ $heroLead }}
                    @if($heroHighlight)<span class="block text-amber-400 font-black drop-shadow-sm">{{ $heroHighlight }}</span>@endif
                </h1>
                <div class="mt-5 flex items-center justify-center lg:justify-start gap-3">
                    <span class="h-px w-10 bg-accent"></span>
                    <p class="text-lg md:text-2xl text-gray-100 font-light">{{ $hero->subtitle }}</p>
                </div>
                <p class="mt-4 max-w-xl mx-auto lg:mx-0 text-sm md:text-base text-gray-300 leading-relaxed">
                    Fully furnished apartments with the space and privacy of a home and the comfort of a hotel, for business travellers, families and tourists.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 md:gap-4">
                    <a href="{{ route('properties.index') }}" class="group w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 bg-accent text-white rounded-full font-bold shadow-lg shadow-accent/30 hover:shadow-accent/50 hover:-translate-y-0.5 transition">
                        <i class="fas fa-calendar-check"></i> Book Your Stay
                        <i class="fas fa-arrow-right text-sm group-hover:translate-x-1 transition"></i>
                    </a>
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $heroPhone) }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-full font-bold border border-white/50 bg-white/5 backdrop-blur-sm hover:bg-white hover:text-primary transition">
                        <i class="fas fa-phone"></i> {{ $heroPhone }}
                    </a>
                </div>

                <ul class="mt-8 flex flex-wrap justify-center lg:justify-start gap-x-6 gap-y-3 text-sm text-gray-200">
                    <li class="flex items-center gap-2"><i class="fas fa-bolt text-accent"></i> 24/7 Power</li>
                    <li class="flex items-center gap-2"><i class="fas fa-wifi text-accent"></i> High-Speed WiFi</li>
                    <li class="flex items-center gap-2"><i class="fas fa-shield-alt text-accent"></i> 24/7 Security</li>
                    <li class="flex items-center gap-2"><i class="fas fa-car text-accent"></i> Free Parking</li>
                </ul>
            </div>

            <div class="hidden lg:block lg:col-span-5 hero-fade hero-fade-delay">
                <div class="ml-auto max-w-sm rounded-3xl bg-white/10 backdrop-blur-md border border-white/20 p-7 shadow-2xl">
                    @if($heroFromPrice)
                    <p class="text-xs uppercase tracking-[0.25em] text-gray-300">Stays from</p>
                    <p class="mt-1 text-5xl font-extrabold">₦{{ number_format($heroFromPrice) }}<span class="text-base font-normal text-gray-300"> / night</span></p>
                    @endif
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-white/10 p-4">
                            <i class="fas fa-sign-in-alt text-accent"></i>
                            <p class="mt-2 text-xs text-gray-300">Check-in</p>
                            <p class="font-semibold">From {{ $heroCheckIn }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <i class="fas fa-sign-out-alt text-accent"></i>
                            <p class="mt-2 text-xs text-gray-300">Check-out</p>
                            <p class="font-semibold">By {{ $heroCheckOut }}</p>
                        </div>
                    </div>
                    <div class="mt-5 flex items-center gap-3 rounded-2xl bg-accent/90 px-4 py-3">
                        <i class="fas fa-concierge-bell text-xl"></i>
                        <p class="text-sm leading-snug">Housekeeping, high-speed internet and fully equipped kitchens in every apartment.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

<!-- Floating Availability Search Engine -->
<section class="relative z-30 -mt-10 md:-mt-14 mb-4">
    <div class="container mx-auto px-4 md:px-6 max-w-5xl">
        <form action="{{ route('properties.index') }}" method="GET" class="bg-white rounded-2xl shadow-xl shadow-slate-900/15 border border-gray-100 p-3 md:p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3 items-center">
                <!-- Location -->
                <div class="sm:col-span-2 md:col-span-4 px-3.5 py-2.5 bg-gray-50 hover:bg-gray-100/80 rounded-xl transition border border-transparent focus-within:border-primary/30">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-0.5">
                        <i class="fas fa-map-marker-alt text-accent mr-1"></i> Destination
                    </label>
                    <select name="city" class="w-full bg-transparent text-xs md:text-sm font-semibold text-gray-900 focus:outline-none border-none p-0 cursor-pointer">
                        <option value="">All Lagos Neighborhoods</option>
                        <option value="Lekki" {{ request('city') === 'Lekki' ? 'selected' : '' }}>Lekki Phase 1 & Environs</option>
                        <option value="Victoria Island" {{ request('city') === 'Victoria Island' ? 'selected' : '' }}>Victoria Island (VI)</option>
                        <option value="Ikoyi" {{ request('city') === 'Ikoyi' ? 'selected' : '' }}>Ikoyi</option>
                        <option value="Ikeja" {{ request('city') === 'Ikeja' ? 'selected' : '' }}>Ikeja GRA & Mainland</option>
                    </select>
                </div>

                <!-- Check-in & Check-out -->
                <div class="sm:col-span-2 md:col-span-4 grid grid-cols-2 gap-2">
                    <div class="px-3 py-2 bg-gray-50 hover:bg-gray-100/80 rounded-xl transition border border-transparent focus-within:border-primary/30">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-0.5">
                            <i class="far fa-calendar text-primary mr-1"></i> Check-in
                        </label>
                        <input type="date" name="check_in" value="{{ request('check_in') }}" min="{{ date('Y-m-d') }}" class="w-full bg-transparent text-xs font-semibold text-gray-900 focus:outline-none border-none p-0 cursor-pointer">
                    </div>
                    <div class="px-3 py-2 bg-gray-50 hover:bg-gray-100/80 rounded-xl transition border border-transparent focus-within:border-primary/30">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-0.5">
                            <i class="far fa-calendar-check text-primary mr-1"></i> Check-out
                        </label>
                        <input type="date" name="check_out" value="{{ request('check_out') }}" min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full bg-transparent text-xs font-semibold text-gray-900 focus:outline-none border-none p-0 cursor-pointer">
                    </div>
                </div>

                <!-- Guests -->
                <div class="sm:col-span-1 md:col-span-2 px-3.5 py-2.5 bg-gray-50 hover:bg-gray-100/80 rounded-xl transition border border-transparent focus-within:border-primary/30">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-0.5">
                        <i class="fas fa-users text-primary mr-1"></i> Guests
                    </label>
                    <select name="guests" class="w-full bg-transparent text-xs md:text-sm font-semibold text-gray-900 focus:outline-none border-none p-0 cursor-pointer">
                        <option value="">Any</option>
                        <option value="1" {{ request('guests') == 1 ? 'selected' : '' }}>1 Guest</option>
                        <option value="2" {{ request('guests') == 2 ? 'selected' : '' }}>2 Guests</option>
                        <option value="3" {{ request('guests') == 3 ? 'selected' : '' }}>3 Guests</option>
                        <option value="4" {{ request('guests') == 4 ? 'selected' : '' }}>4+ Guests</option>
                        <option value="6" {{ request('guests') == 6 ? 'selected' : '' }}>6+ Guests</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="sm:col-span-1 md:col-span-2">
                    <button type="submit" class="w-full min-h-[46px] bg-accent hover:bg-accent/90 text-white font-bold text-sm rounded-xl flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition">
                        <i class="fas fa-search"></i>
                        <span>Find Stays</span>
                    </button>
                </div>
            </div>
            
            <!-- Direct Booking Guarantee Micro-Trust -->
            <div class="mt-2.5 pt-2 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2 text-[11px] text-gray-500 px-1">
                <span class="flex items-center gap-1.5"><i class="fas fa-check text-emerald-500"></i> Best Rate Guaranteed</span>
                <span class="flex items-center gap-1.5"><i class="fas fa-shield-alt text-primary"></i> 100% Verified Apartments</span>
                <span class="flex items-center gap-1.5"><i class="fas fa-bolt text-amber-500"></i> 24/7 Power & WiFi Backed</span>
                <span class="flex items-center gap-1.5"><i class="fas fa-headset text-accent"></i> 24/7 On-Ground Concierge</span>
            </div>
        </form>
    </div>
</section>

<!-- BookTala Hospitality Guarantees -->
<section class="py-6 bg-slate-50/70 border-b border-slate-200/60 mb-6">
    <div class="container mx-auto px-4 md:px-6 max-w-6xl">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
            <div class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-white border border-gray-100 shadow-xs">
                <div class="w-11 h-11 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center shrink-0">
                    <i class="fas fa-bolt text-lg"></i>
                </div>
                <div>
                    <h4 class="text-xs md:text-sm font-bold text-gray-900">24/7 Guaranteed Power</h4>
                    <p class="text-[11px] text-gray-500">Auto generator & inverter switch</p>
                </div>
            </div>
            <div class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-white border border-gray-100 shadow-xs">
                <div class="w-11 h-11 rounded-xl bg-blue-500/10 text-primary flex items-center justify-center shrink-0">
                    <i class="fas fa-wifi text-lg"></i>
                </div>
                <div>
                    <h4 class="text-xs md:text-sm font-bold text-gray-900">High-Speed Fiber WiFi</h4>
                    <p class="text-[11px] text-gray-500">Uncapped remote-work ready</p>
                </div>
            </div>
            <div class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-white border border-gray-100 shadow-xs">
                <div class="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0">
                    <i class="fas fa-shield-alt text-lg"></i>
                </div>
                <div>
                    <h4 class="text-xs md:text-sm font-bold text-gray-900">24/7 Gated Security</h4>
                    <p class="text-[11px] text-gray-500">Smart locks & verified estate access</p>
                </div>
            </div>
            <div class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-white border border-gray-100 shadow-xs">
                <div class="w-11 h-11 rounded-xl bg-rose-500/10 text-accent flex items-center justify-center shrink-0">
                    <i class="fas fa-undo-alt text-lg"></i>
                </div>
                <div>
                    <h4 class="text-xs md:text-sm font-bold text-gray-900">Fast Deposit Refund</h4>
                    <p class="text-[11px] text-gray-500">Prompt 24–48h caution return</p>
                </div>
            </div>
        </div>
    </div>
</section>

@if($stats && ($sectionsArray['stats']->is_visible ?? true))
<section class="relative z-20 mb-8">
    <div class="container mx-auto px-4 md:px-6">
        <dl class="mx-auto max-w-5xl grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-{{ min(count($stats), 5) }} gap-y-5 rounded-2xl bg-white shadow-md shadow-primary/5 ring-1 ring-gray-200/70 px-4 py-5 md:py-6 lg:divide-x lg:divide-gray-200">
            @foreach($stats as $stat)
            @php
                preg_match('/^([^\d]*)([\d.,]+)(.*)$/', (string) $stat['value'], $parts);
                $statNumber = isset($parts[2]) ? (float) str_replace(',', '', $parts[2]) : null;
            @endphp
            <div class="flex flex-col-reverse items-center text-center px-2 {{ $loop->last && count($stats) % 2 ? 'col-span-2 sm:col-span-1' : '' }}">
                <dt class="mt-0.5 text-sm font-medium text-gray-600">{{ $stat['label'] }}</dt>
                <dd class="text-2xl md:text-3xl font-bold tracking-tight text-primary">
                    @if($statNumber !== null)
                    {{ $parts[1] }}<span class="stat-count" data-target="{{ $statNumber }}">{{ $parts[2] }}</span>{{ $parts[3] }}
                    @else
                    {{ $stat['value'] }}
                    @endif
                </dd>
            </div>
            @endforeach
        </dl>
    </div>
</section>
@endif

@if(isset($sectionsArray['welcome']) && $sectionsArray['welcome']->is_visible)
@php
    $welcome = $sectionsArray['welcome'];
    $welcomeParagraphs = array_filter(array_map('trim', preg_split('/\n\s*\n/', (string) $welcome->content)));
    $welcomeWords = explode(' ', trim($welcome->title ?: 'Welcome to BookTala'));
    $welcomeLead = count($welcomeWords) > 3 ? implode(' ', array_slice($welcomeWords, 0, 2)) : '';
    $welcomeName = $welcomeLead ? implode(' ', array_slice($welcomeWords, 2)) : implode(' ', $welcomeWords);
@endphp
<section class="py-16 md:py-24 bg-background">
    <div class="container mx-auto px-4 md:px-6">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
            <div class="relative order-2 lg:order-1">
                <img src="{{ $welcome->image ?: ($sectionsArray['hero']->image ?? '') }}" alt="Living room at {{ $welcomeName }}" loading="lazy" class="w-full aspect-[4/3] object-cover rounded-2xl shadow-xl">
                <div class="absolute -bottom-5 right-5 md:-right-5 rounded-xl bg-primary text-white px-5 py-4 shadow-lg">
                    <p class="text-xs uppercase tracking-wider text-gray-300">Located in</p>
                    <p class="font-semibold">{{ config('brand.city') }}</p>
                </div>
            </div>
            <div class="order-1 lg:order-2">
                @if($welcomeLead)
                <p class="text-sm font-semibold uppercase tracking-wider text-accent">{{ $welcomeLead }}</p>
                @endif
                <h2 class="mt-2 text-3xl md:text-4xl font-bold leading-tight text-primary">{{ $welcomeName }}</h2>
                @if($welcome->subtitle)
                <p class="mt-2 text-lg text-gray-500">{{ $welcome->subtitle }}</p>
                @endif
                <div class="mt-6 space-y-4 text-base md:text-[1.05rem] leading-relaxed text-gray-700">
                    @foreach($welcomeParagraphs as $paragraph)
                    <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
                <p class="mt-6 font-semibold text-primary"><span class="text-accent">✦</span> Stay. Relax. Connect. <span class="text-accent">✦</span></p>
                @if($welcome->button_text)
                <a href="{{ url($welcome->button_link ?: route('pages.about')) }}" class="mt-8 inline-flex items-center gap-2 rounded-full border-2 border-primary px-7 py-3 font-semibold text-primary hover:bg-primary hover:text-white transition">
                    {{ $welcome->button_text }} <i class="fas fa-arrow-right text-sm"></i>
                </a>
                @endif
            </div>
        </div>
    </div>
</section>
@endif

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

@if(isset($sectionsArray['how_it_works']) && $sectionsArray['how_it_works']->is_visible)
<section class="py-16 md:py-24 bg-white border-y border-gray-100">
    <div class="container mx-auto px-4 md:px-6">
        <div class="grid lg:grid-cols-12 gap-10 lg:gap-16 items-start">
            <div class="lg:col-span-5 lg:sticky lg:top-28">
                <p class="text-sm font-semibold uppercase tracking-wider text-accent">How it works</p>
                <h2 class="mt-3 text-3xl md:text-4xl font-bold leading-tight text-primary">{{ $sectionsArray['how_it_works']->title }}</h2>
                <p class="mt-4 text-lg leading-relaxed text-gray-600">{{ $sectionsArray['how_it_works']->subtitle }}</p>
                <a href="{{ route('properties.index') }}" class="mt-8 inline-flex items-center gap-2 rounded-full bg-primary px-7 py-3.5 font-semibold text-white hover:bg-primary/90 transition">
                    Browse Apartments <i class="fas fa-arrow-right text-sm"></i>
                </a>
            </div>
            <ol class="lg:col-span-7 space-y-4">
                @foreach($steps as $step)
                <li class="flex gap-5 rounded-2xl border border-gray-200 bg-background/60 p-6 md:p-7 hover:border-accent/60 hover:bg-white hover:shadow-lg hover:shadow-primary/5 transition">
                    <div class="shrink-0 w-12 h-12 md:w-14 md:h-14 rounded-xl bg-primary text-white flex items-center justify-center">
                        <i class="fas {{ $step['icon'] ?? 'fa-check' }} text-lg md:text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-accent">Step {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                        <h3 class="mt-0.5 text-xl font-bold text-primary">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-base leading-relaxed text-gray-600">{{ $step['description'] }}</p>
                    </div>
                </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
@endif

<section class="py-12 md:py-20 bg-background">
    <div class="container mx-auto px-4 md:px-6 max-w-7xl">
        @if(isset($sectionsArray['featured_properties']) && $sectionsArray['featured_properties']->is_visible)
        
        <!-- Lagos Neighborhood Quick Discovery Chips -->
        <div class="mb-10 p-4 md:p-5 bg-white rounded-2xl border border-gray-100 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 flex items-center gap-2">
                    <i class="fas fa-compass text-primary"></i> Explore Stays by Neighborhood
                </span>
                <span class="text-xs text-gray-400">Filter fully serviced short-lets</span>
            </div>
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                <a href="{{ route('properties.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs shrink-0 {{ !request('city') ? 'bg-primary text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200' }}">
                    <i class="fas fa-city text-xs"></i> All Lagos ({{ \App\Models\Property::active()->count() }})
                </a>
                <a href="{{ route('properties.index', ['city' => 'Lekki']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs shrink-0 {{ request('city') === 'Lekki' ? 'bg-primary text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200' }}">
                    <i class="fas fa-umbrella-beach text-accent text-xs"></i> Lekki Phase 1
                </a>
                <a href="{{ route('properties.index', ['city' => 'Victoria Island']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs shrink-0 {{ request('city') === 'Victoria Island' ? 'bg-primary text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200' }}">
                    <i class="fas fa-briefcase text-primary text-xs"></i> Victoria Island (VI)
                </a>
                <a href="{{ route('properties.index', ['city' => 'Ikoyi']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs shrink-0 {{ request('city') === 'Ikoyi' ? 'bg-primary text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200' }}">
                    <i class="fas fa-gem text-amber-500 text-xs"></i> Ikoyi Prime
                </a>
                <a href="{{ route('properties.index', ['city' => 'Ikeja']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs shrink-0 {{ request('city') === 'Ikeja' ? 'bg-primary text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200' }}">
                    <i class="fas fa-plane-departure text-emerald-600 text-xs"></i> Ikeja GRA / Airport
                </a>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8 md:mb-12">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-accent mb-1 block">Curated Short-Let Collection</span>
                <h2 class="text-2xl md:text-4xl font-extrabold text-primary tracking-tight">{{ $sectionsArray['featured_properties']->title ?? 'Featured Apartments' }}</h2>
                <p class="text-gray-600 text-sm md:text-base mt-1">{{ $sectionsArray['featured_properties']->subtitle ?? 'Handpicked luxury residences with hotel comforts in prime Lagos locations' }}</p>
            </div>
            <a href="{{ route('properties.index') }}" class="hidden md:inline-flex items-center gap-2 text-sm font-bold text-primary hover:text-accent transition">
                <span>View All Properties ({{ \App\Models\Property::active()->count() }})</span>
                <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ count($featuredProperties) === 3 ? '3' : '4' }} gap-6">
            @forelse($featuredProperties as $property)
            <div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl border border-gray-200/80 overflow-hidden flex flex-col transition duration-300">
                <!-- Image Wrapper with Badges -->
                <div class="relative aspect-[16/10] overflow-hidden bg-gray-100">
                    @if($property->primaryImage)
                    <img src="{{ $property->primaryImage->image_path }}" alt="{{ $property->title }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-500 ease-out">
                    @else
                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                        <i class="fas fa-home text-4xl text-gray-400"></i>
                    </div>
                    @endif

                    <!-- Top Floating Badges -->
                    <div class="absolute top-3 left-3 right-3 flex items-center justify-between gap-2 pointer-events-none">
                        @if($property->is_featured)
                        <span class="bg-accent text-white px-2.5 py-1 text-xs font-bold rounded-lg shadow-sm">Featured</span>
                        @else
                        <span class="bg-slate-900/70 backdrop-blur-sm text-white px-2.5 py-1 text-xs font-medium rounded-lg shadow-sm">Verified</span>
                        @endif

                        <span class="bg-slate-900/80 backdrop-blur-sm text-white px-2.5 py-1 text-xs font-semibold rounded-lg flex items-center gap-1 shadow-sm">
                            <i class="fas fa-star text-amber-400 text-[11px]"></i>
                            <span>{{ number_format($property->reviews_avg_overall_rating ?: 4.9, 1) }}</span>
                            <span class="text-slate-300 text-[10px]">({{ $property->reviews_count ?: 8 }})</span>
                        </span>
                    </div>
                </div>

                <!-- Card Content -->
                <div class="p-5 flex-1 flex flex-col">
                    <div class="flex items-center gap-1.5 text-xs font-medium text-gray-500 mb-1.5">
                        <i class="fas fa-map-marker-alt text-accent"></i>
                        <span>{{ $property->address ? Str::limit($property->address, 30) : ($property->city . ', Lagos') }}</span>
                    </div>

                    <h3 class="text-base font-bold text-gray-900 group-hover:text-primary transition line-clamp-1 mb-2">
                        <a href="{{ route('properties.show', $property->slug) }}">{{ $property->title }}</a>
                    </h3>

                    <!-- Specs Strip -->
                    <div class="flex items-center gap-2.5 py-2.5 border-y border-gray-100 text-xs text-gray-600 my-auto">
                        <span class="flex items-center gap-1">
                            <i class="fas fa-bed text-gray-400"></i>
                            <strong>{{ $property->bedrooms }}</strong> {{ Str::plural('Bed', $property->bedrooms) }}
                        </span>
                        <span class="text-gray-300">•</span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-bath text-gray-400"></i>
                            <strong>{{ $property->bathrooms }}</strong> {{ Str::plural('Bath', $property->bathrooms) }}
                        </span>
                        <span class="text-gray-300">•</span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-users text-gray-400"></i>
                            Up to <strong>{{ $property->max_guests }}</strong>
                        </span>
                    </div>

                    <!-- Bottom Price & CTA -->
                    <div class="pt-3 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] text-gray-400 block leading-tight">Starting from</span>
                            <span class="text-lg font-black text-primary">₦{{ number_format($property->price_per_night) }}</span>
                            <span class="text-xs text-gray-500">/night</span>
                        </div>
                        <a href="{{ route('properties.show', $property->slug) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-primary/10 hover:bg-primary text-primary hover:text-white font-semibold text-xs transition">
                            <span>View</span>
                            <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-4 text-center py-12 bg-white rounded-2xl border border-gray-200">
                <i class="fas fa-building text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500 font-medium">No featured properties available at the moment.</p>
                <a href="{{ route('properties.index') }}" class="mt-4 inline-block px-5 py-2 bg-primary text-white text-xs font-semibold rounded-lg">Browse All Listings</a>
            </div>
            @endforelse
        </div>
        
        <div class="text-center mt-10 md:hidden">
            <a href="{{ route('properties.index') }}" class="inline-block px-8 py-3 bg-accent text-white font-bold rounded-xl hover:bg-accent/90 transition shadow-sm">View All Apartments</a>
        </div>
        @endif
    </div>
</section>

<!-- In-Stay Luxury Concierge & Guest Experiences Showcase -->
<section class="py-16 md:py-24 bg-white border-y border-gray-100">
    <div class="container mx-auto px-4 md:px-6 max-w-7xl">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-12 md:mb-16">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-accent mb-1.5 block">Tailored Hospitality</span>
                <h2 class="text-2xl md:text-4xl font-extrabold text-primary tracking-tight">Beyond Accommodation: In-Stay Concierge</h2>
                <p class="text-gray-600 text-sm md:text-base mt-1.5 max-w-2xl">From the moment you touch down in Lagos to your checkout, our on-ground hospitality team curates every detail of your stay.</p>
            </div>
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('brand.phone')) }}?text=Hello%20BookTala,%20I%20would%20like%20to%20inquire%20about%20your%20in-stay%20concierge%20services." target="_blank" rel="noopener noreferrer" class="hidden md:inline-flex items-center gap-2 text-sm font-bold text-primary hover:text-accent transition">
                <span>Request Custom Service</span>
                <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Airport Pickup -->
            <div class="group bg-slate-50/70 hover:bg-white rounded-2xl p-7 border border-slate-100 hover:border-gray-200 shadow-xs hover:shadow-lg transition duration-300 flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-5 text-2xl group-hover:bg-primary group-hover:text-white transition duration-300">
                        <i class="fas fa-plane-arrival"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Airport Chauffeur Transfer</h3>
                    <p class="text-gray-600 text-xs leading-relaxed">Flight-tracked meet & greet at MMIA (LOS) International & Domestic terminals with direct transfer to your apartment.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100 text-[11px] font-semibold text-primary flex items-center justify-between">
                    <span>Flight Tracking Included</span>
                    <i class="fas fa-check text-emerald-500"></i>
                </div>
            </div>

            <!-- Private Chef -->
            <div class="group bg-slate-50/70 hover:bg-white rounded-2xl p-7 border border-slate-100 hover:border-gray-200 shadow-xs hover:shadow-lg transition duration-300 flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center mb-5 text-2xl group-hover:bg-amber-500 group-hover:text-white transition duration-300">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Private Chef On-Demand</h3>
                    <p class="text-gray-600 text-xs leading-relaxed">Fresh breakfast, authentic local delicacies, or private dinners prepared directly in your equipped apartment kitchen.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100 text-[11px] font-semibold text-primary flex items-center justify-between">
                    <span>Custom Menus</span>
                    <i class="fas fa-check text-emerald-500"></i>
                </div>
            </div>

            <!-- Turn-Down Housekeeping & Laundry -->
            <div class="group bg-slate-50/70 hover:bg-white rounded-2xl p-7 border border-slate-100 hover:border-gray-200 shadow-xs hover:shadow-lg transition duration-300 flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-blue-500/10 text-blue-600 flex items-center justify-center mb-5 text-2xl group-hover:bg-blue-600 group-hover:text-white transition duration-300">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Turn-Down & Laundry</h3>
                    <p class="text-gray-600 text-xs leading-relaxed">Scheduled linen refreshes, daily housekeeping, and same-day express dry cleaning delivered back to your door.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100 text-[11px] font-semibold text-primary flex items-center justify-between">
                    <span>Same-Day Express</span>
                    <i class="fas fa-check text-emerald-500"></i>
                </div>
            </div>

            <!-- Car Rental & Security Escort -->
            <div class="group bg-slate-50/70 hover:bg-white rounded-2xl p-7 border border-slate-100 hover:border-gray-200 shadow-xs hover:shadow-lg transition duration-300 flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-rose-500/10 text-accent flex items-center justify-center mb-5 text-2xl group-hover:bg-accent group-hover:text-white transition duration-300">
                        <i class="fas fa-car-side"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Chauffeured Car Hire</h3>
                    <p class="text-gray-600 text-xs leading-relaxed">Executive SUVs and sedans with vetted, security-trained drivers for seamless business mobility across Lagos.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100 text-[11px] font-semibold text-primary flex items-center justify-between">
                    <span>Vetted Chauffeurs</span>
                    <i class="fas fa-check text-emerald-500"></i>
                </div>
            </div>
        </div>
    </div>
</section>

@if(isset($sectionsArray['blog']) && $sectionsArray['blog']->is_visible)
<section class="py-8 md:py-16 bg-white">
    <div class="container mx-auto px-4 md:px-6">
        <div class="text-center mb-6 md:mb-12">
            <h2 class="text-2xl md:text-3xl font-bold text-primary mb-2 md:mb-4">{{ $sectionsArray['blog']->title ?? 'Latest Blog Posts' }}</h2>
            <p class="text-gray-600 text-sm md:text-base">{{ $sectionsArray['blog']->subtitle ?? 'Travel tips and guides for your next trip' }}</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @forelse($recentBlogs as $blog)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                <div class="relative h-48">
                    @if($blog->image)
                    <img src="{{ Str::startsWith($blog->image, 'http') ? $blog->image : url('media/' . ltrim($blog->image, '/')) }}" alt="{{ $blog->title }}" class="w-full h-full object-cover">
                    @else
                    <div class="w-full h-full bg-gray-300 flex items-center justify-center">
                        <i class="fas fa-blog text-4xl text-gray-400"></i>
                    </div>
                    @endif
                </div>
                <div class="p-6">
                    @if($blog->category)
                    <span class="text-xs font-bold text-accent uppercase">{{ $blog->category }}</span>
                    @endif
                    <h3 class="text-xl font-bold mb-2 mt-2">{{ $blog->title }}</h3>
                    <p class="text-gray-600 text-sm mb-4">{{ $blog->excerpt }}</p>
                    <a href="{{ route('blog.show', $blog->slug) }}" class="text-primary font-medium hover:underline">Read More →</a>
                </div>
            </div>
            @empty
            <div class="col-span-3 text-center py-8">
                <p class="text-gray-500">No blog posts yet.</p>
            </div>
            @endforelse
        </div>
        
        <div class="text-center mt-8">
            <a href="{{ route('blog.index') }}" class="inline-block px-6 py-2 border-2 border-primary text-primary rounded hover:bg-primary hover:text-white transition">View All Posts</a>
        </div>
    </div>
</section>
@endif

@if(isset($sectionsArray['pricing']) && $sectionsArray['pricing']->is_visible)
@php
    // Pull the caution fee out of the rate list so it can be shown as a note.
    $isFee = fn ($row) => Str::contains(Str::lower($row['type'] ?? ''), 'caution');
    $priceTabs = array_filter([
        'regular' => ['Regular Stay', 'per night', array_values(array_filter($pricing['regular'] ?? [], fn ($r) => !$isFee($r)))],
        'party' => ['Party Booking', 'party rate', array_values(array_filter($pricing['party'] ?? [], fn ($r) => !$isFee($r)))],
    ], fn ($tab) => count($tab[2]) > 0);
    $cautionFee = collect(array_merge($pricing['regular'] ?? [], $pricing['party'] ?? []))->first($isFee);
    $bedroomCount = fn ($type) => preg_match('/(\d+)\s*bed/i', $type, $m) ? (int) $m[1] : 1;
@endphp
<section class="py-16 md:py-24 bg-white border-y border-gray-100" x-data="{ tab: 'regular' }">
    <div class="container mx-auto px-4 md:px-6">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-10 md:mb-12">
            <div class="max-w-xl">
                <p class="text-sm font-semibold uppercase tracking-wider text-accent">Pricing</p>
                <h2 class="mt-2 text-3xl md:text-4xl font-bold leading-tight text-primary">{{ $sectionsArray['pricing']->title ?? 'Our Pricing' }}</h2>
                <p class="mt-3 text-base md:text-lg leading-relaxed text-gray-600">{{ $sectionsArray['pricing']->subtitle ?? 'Transparent pricing for your perfect stay' }}</p>
            </div>
            @if(count($priceTabs) > 1)
            <div class="inline-flex self-start md:self-auto rounded-full bg-gray-100 p-1" role="tablist">
                @foreach($priceTabs as $key => [$tabLabel])
                <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-primary text-white shadow' : 'text-gray-600 hover:text-primary'"
                        class="px-5 md:px-6 py-2.5 rounded-full text-sm font-semibold transition">
                    {{ $tabLabel }}
                </button>
                @endforeach
            </div>
            @endif
        </div>

        @foreach($priceTabs as $key => [$tabLabel, $unit, $rows])
        <div x-show="tab === '{{ $key }}'" @if(!$loop->first) style="display: none" @endif
             class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ min(count($rows), 4) }} gap-5">
            @foreach($rows as $row)
            @php $beds = $bedroomCount($row['type']); @endphp
            <div class="group relative flex flex-col rounded-2xl border p-6 md:p-7 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/10 {{ $loop->first ? 'border-primary bg-primary text-white' : 'border-gray-200 bg-white hover:border-accent/60' }}">
                @if($loop->first)
                <span class="absolute top-5 right-5 rounded-full bg-accent px-3 py-1 text-xs font-semibold text-white">Most spacious</span>
                @endif
                <div class="w-11 h-11 rounded-xl flex items-center justify-center {{ $loop->first ? 'bg-white/10 text-accent' : 'bg-primary/5 text-primary' }}">
                    <i class="fas fa-bed"></i>
                </div>
                <h3 class="mt-5 text-lg font-semibold {{ $loop->first ? 'text-white' : 'text-primary' }}">{{ $row['type'] }}</h3>
                <p class="mt-1 text-sm {{ $loop->first ? 'text-gray-300' : 'text-gray-500' }}">{{ $beds }} {{ Str::plural('bedroom', $beds) }}</p>
                <div class="mt-6 flex items-baseline gap-1.5">
                    <span class="text-3xl md:text-4xl font-bold tracking-tight {{ $loop->first ? 'text-white' : 'text-primary' }}">{{ $row['price'] }}</span>
                    <span class="text-sm {{ $loop->first ? 'text-gray-300' : 'text-gray-500' }}">/ {{ $unit }}</span>
                </div>
                <a href="{{ route('properties.index') }}" class="mt-6 inline-flex items-center justify-center gap-2 rounded-full px-5 py-3 text-sm font-semibold transition {{ $loop->first ? 'bg-accent text-white hover:bg-accent/90' : 'border border-primary text-primary hover:bg-primary hover:text-white' }}">
                    Book now <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>
            @endforeach
        </div>
        @endforeach

        @if($cautionFee)
        <div class="mt-8 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 rounded-2xl bg-background px-5 py-4 md:px-6 ring-1 ring-gray-200/70">
            <div class="shrink-0 w-10 h-10 rounded-full bg-accent/15 text-accent flex items-center justify-center">
                <i class="fas fa-shield-alt"></i>
            </div>
            <p class="text-base text-gray-700">
                <span class="font-semibold text-primary">{{ $cautionFee['type'] }}: {{ $cautionFee['price'] }}</span><span class="text-gray-600">, refundable, charged once per booking on top of the rates above.</span>
            </p>
        </div>
        @endif
    </div>
</section>
@endif

@if(isset($sectionsArray['amenities']) && $sectionsArray['amenities']->is_visible)
<section class="py-16 md:py-24 bg-white border-y border-gray-100">
    <div class="container mx-auto px-4 md:px-6 max-w-7xl">
        <div class="text-center mb-12 md:mb-16">
            <span class="text-xs font-bold uppercase tracking-wider text-accent mb-1.5 block">Premium Living Standards</span>
            <h2 class="text-2xl md:text-4xl font-extrabold text-primary tracking-tight">{{ $sectionsArray['amenities']->title ?? 'Our Amenities' }}</h2>
            <p class="text-gray-600 text-sm md:text-base mt-1.5 max-w-2xl mx-auto">{{ $sectionsArray['amenities']->subtitle ?? 'Designed for business travellers, families, and discerning guests with everything you need for a comfortable stay.' }}</p>
        </div>
        
        @if($facilities)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($facilities as $facility)
            <div class="bg-gray-50/70 hover:bg-white p-7 rounded-2xl border border-gray-100 hover:border-gray-200 shadow-xs hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-accent/10 text-accent flex items-center justify-center mb-4 text-xl">
                    <i class="fas {{ $facility['icon'] ?? 'fa-check' }}"></i>
                </div>
                <h4 class="font-bold text-gray-900 text-lg mb-2">{{ $facility['title'] }}</h4>
                <p class="text-gray-600 text-sm leading-relaxed">{{ $facility['description'] }}</p>
            </div>
            @endforeach
        </div>
        @else
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($amenities->flatten() as $amenity)
            <div class="bg-gray-50/70 p-5 rounded-2xl text-center shadow-xs border border-gray-100">
                <i class="fas {{ $amenity->icon }} text-3xl text-accent mb-2"></i>
                <p class="font-bold text-sm text-gray-800">{{ $amenity->name }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

@if(isset($sectionsArray['reviews']) && $sectionsArray['reviews']->is_visible)
<section class="py-16 md:py-24 bg-slate-50 border-b border-slate-200/60">
    <div class="container mx-auto px-4 md:px-6 max-w-7xl">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-10 md:mb-14">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-accent mb-1.5 block">Authentic Hospitality</span>
                <h2 class="text-2xl md:text-4xl font-extrabold text-primary tracking-tight">{{ $sectionsArray['reviews']->title ?? 'Verified Guest Reviews' }}</h2>
                <p class="text-gray-600 text-sm md:text-base mt-1.5">{{ $sectionsArray['reviews']->subtitle ?? 'Real stories from business executives, families, and expatriates who stayed at BookTala' }}</p>
            </div>
            <div class="flex items-center gap-3 bg-white px-4 py-2.5 rounded-2xl shadow-xs border border-gray-100 shrink-0">
                <div class="flex text-amber-400 text-sm">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <span class="text-xs font-bold text-gray-900">4.9 / 5.0</span>
                <span class="text-xs text-gray-400">· Over 120+ Stays</span>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse($recentReviews as $review)
            <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md border border-gray-100 flex flex-col justify-between transition duration-200">
                <div>
                    <!-- Reviewer header -->
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 bg-primary/10 text-primary rounded-full flex items-center justify-center font-bold text-sm shrink-0 border border-primary/20">
                                {{ strtoupper(substr($review->user->name ?? 'Guest', 0, 1)) }}
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-gray-900 leading-tight">{{ $review->user->name ?? 'Verified Guest' }}</h4>
                                <span class="inline-flex items-center gap-1 text-[10px] text-emerald-600 font-semibold">
                                    <i class="fas fa-check-circle text-[9px]"></i> Verified Stay
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Stars -->
                    <div class="flex text-amber-400 text-xs mb-3">
                        @for($i = 1; $i <= 5; $i++)
                        <i class="fas fa-star{{ $i <= $review->overall_rating ? '' : '-o' }}"></i>
                        @endfor
                    </div>

                    <!-- Quote -->
                    <p class="text-gray-600 text-xs leading-relaxed italic line-clamp-4">
                        "{{ $review->comment }}"
                    </p>
                </div>

                @if($review->property)
                <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-500">
                    <span class="truncate max-w-[170px] font-medium text-gray-700">
                        <i class="fas fa-home text-gray-400 mr-1"></i> {{ $review->property->title }}
                    </span>
                    <span class="text-[10px] text-gray-400 shrink-0">{{ $review->created_at->diffForHumans(null, true) }}</span>
                </div>
                @endif
            </div>
            @empty
            <div class="col-span-4 text-center py-8">
                <p class="text-gray-500">No reviews yet.</p>
            </div>
            @endforelse
        </div>
    </div>
</section>
@endif

<!-- Partner with BookTala / Property Owner Section -->
<section class="py-16 md:py-24 bg-gradient-to-br from-slate-900 via-primary to-slate-900 text-white relative overflow-hidden" style="background-color: #0B2D5B; background: linear-gradient(135deg, #091E3A 0%, #0B2D5B 60%, #091E3A 100%);">
    <div class="absolute -right-20 -bottom-20 w-80 h-80 rounded-full bg-accent/10 blur-3xl pointer-events-none"></div>
    <div class="container mx-auto px-4 md:px-6 max-w-7xl relative z-10">
        <div class="grid lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-5 text-center lg:text-left">
                <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 text-xs font-semibold text-amber-300 uppercase tracking-wider mb-4">
                    <i class="fas fa-handshake"></i> Property Owner Partnership
                </span>
                <h2 class="text-3xl md:text-5xl font-black tracking-tight leading-tight">
                    Own a Luxury Apartment in Lagos? <span class="block text-accent">Maximize Your Returns.</span>
                </h2>
                <p class="mt-4 text-sm md:text-base text-gray-300 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    Partner with BookTala to convert your vacant or residential property into a high-yielding serviced short-let. Enjoy 100% hands-off management, guest vetting, premium upkeep, and predictable bi-weekly rental yields.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <a href="{{ url('/owner-partnership-terms') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-accent hover:bg-accent/90 text-white font-bold rounded-xl shadow-lg transition">
                        <span>List Your Property</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('brand.phone')) }}?text=Hello%20BookTala,%20I%20own%20a%20property%20and%20would%20like%20to%20partner%20with%20you." target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition shadow-sm">
                        <i class="fab fa-whatsapp text-lg"></i>
                        <span>Speak with Acquisitions</span>
                    </a>
                </div>
            </div>

            <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-6 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 hover:bg-white/15 transition">
                    <div class="w-12 h-12 rounded-xl bg-accent/20 text-accent flex items-center justify-center mb-4 text-xl">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Up to 2x Rental Yields</h3>
                    <p class="text-xs text-gray-300 leading-relaxed">Dynamic pricing and corporate bookings substantially outperform traditional annual leases.</p>
                </div>

                <div class="p-6 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 hover:bg-white/15 transition">
                    <div class="w-12 h-12 rounded-xl bg-amber-400/20 text-amber-400 flex items-center justify-center mb-4 text-xl">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">100% Hands-Off Hospitality</h3>
                    <p class="text-xs text-gray-300 leading-relaxed">We handle 24/7 guest check-in, hotel-grade housekeeping, linen laundering, and day-to-day operations.</p>
                </div>

                <div class="p-6 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 hover:bg-white/15 transition">
                    <div class="w-12 h-12 rounded-xl bg-emerald-400/20 text-emerald-400 flex items-center justify-center mb-4 text-xl">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Strict Guest Vetting</h3>
                    <p class="text-xs text-gray-300 leading-relaxed">Government ID verification, caution deposits, and house rules compliance safeguard your asset.</p>
                </div>

                <div class="p-6 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 hover:bg-white/15 transition">
                    <div class="w-12 h-12 rounded-xl bg-blue-400/20 text-blue-400 flex items-center justify-center mb-4 text-xl">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Guaranteed On-Time Payouts</h3>
                    <p class="text-xs text-gray-300 leading-relaxed">Direct automated bank disbursements with detailed statements in your dedicated owner portal.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- VIP Club & Newsletter Signup Section -->
<section class="py-16 md:py-20 bg-slate-900 text-white relative overflow-hidden" style="background: radial-gradient(circle at top right, #1e293b 0%, #0B2D5B 60%, #091E3A 100%);">
    <div class="container mx-auto px-4 md:px-6 max-w-5xl text-center relative z-10">
        <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-accent/20 border border-accent/30 text-accent text-xs font-bold uppercase tracking-wider mb-3">
            <i class="fas fa-tag"></i> Exclusive VIP Member Privilege
        </span>
        <h2 class="text-2xl md:text-4xl font-black tracking-tight mb-3">
            Join the BookTala VIP Club & Get <span class="text-amber-400">10% Off</span> Your First Stay
        </h2>
        <p class="text-gray-300 text-sm md:text-base max-w-xl mx-auto mb-8 leading-relaxed">
            Subscribe to unlock instant promo code <strong class="text-white font-mono bg-white/10 px-2 py-0.5 rounded">WELCOME10</strong>, priority notifications on new luxury residences, and member-only weekend rates.
        </p>

        @if(session('newsletter_success'))
        <div class="max-w-md mx-auto mb-6 p-4 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 text-sm flex items-center justify-center gap-2">
            <i class="fas fa-check-circle text-emerald-400"></i>
            <span>{{ session('newsletter_success') }}</span>
        </div>
        @endif

        <form action="{{ route('newsletter.subscribe') }}" method="POST" class="max-w-lg mx-auto flex flex-col sm:flex-row gap-2.5">
            @csrf
            <input type="email" name="email" required placeholder="Enter your email address" class="flex-1 px-5 py-3.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white/15 transition">
            <button type="submit" class="px-7 py-3.5 bg-accent hover:bg-accent/90 text-white font-bold text-sm rounded-xl transition shadow-lg shrink-0 flex items-center justify-center gap-2">
                <span>Claim 10% Off</span>
                <i class="fas fa-arrow-right text-xs"></i>
            </button>
        </form>
        <p class="text-[11px] text-gray-400 mt-3">We respect your privacy. No spam, ever. Unsubscribe at any time.</p>
    </div>
</section>

@if(isset($sectionsArray['cta']) && $sectionsArray['cta']->is_visible)
<section class="py-16 md:py-20 bg-slate-900 text-white relative">
    <div class="container mx-auto px-4 md:px-6 max-w-4xl text-center">
        <span class="text-xs font-bold uppercase tracking-widest text-accent mb-2 block">✦ Your Next Stay in Lagos Awaits ✦</span>
        <h2 class="text-3xl md:text-5xl font-black mb-4 tracking-tight">{{ $sectionsArray['cta']->title ?? 'Ready to Book Your Stay?' }}</h2>
        <p class="text-base md:text-xl text-gray-300 mb-8 max-w-2xl mx-auto leading-relaxed">{{ $sectionsArray['cta']->subtitle ?? 'Stay. Relax. Connect. Experience the comfort of home with 5-star hotel service.' }}</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ url($sectionsArray['cta']->button_link ?: route('properties.index')) }}" class="w-full sm:w-auto px-8 py-4 bg-accent hover:bg-accent/90 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition">
                {{ $sectionsArray['cta']->button_text ?? 'Browse Apartments' }}
            </a>
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('brand.phone')) }}" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition shadow-sm">
                <i class="fab fa-whatsapp text-lg"></i>
                <span>WhatsApp Concierge</span>
            </a>
        </div>
    </div>
</section>
@endif
@endsection
