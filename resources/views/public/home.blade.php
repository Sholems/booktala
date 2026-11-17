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
                    @if($heroHighlight)<span class="block text-accent">{{ $heroHighlight }}</span>@endif
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

@if($stats && ($sectionsArray['stats']->is_visible ?? true))
<section class="relative z-20 -mt-12 md:-mt-14">
    <div class="container mx-auto px-4 md:px-6">
        <dl class="mx-auto max-w-5xl grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-{{ min(count($stats), 5) }} gap-y-5 rounded-2xl bg-white shadow-lg shadow-primary/5 ring-1 ring-gray-200/70 px-4 py-5 md:py-6 lg:divide-x lg:divide-gray-200">
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

<section class="py-8 md:py-16 bg-background">
    <div class="container mx-auto px-4 md:px-6">
        @if(isset($sectionsArray['featured_properties']) && $sectionsArray['featured_properties']->is_visible)
        <div class="text-center mb-6 md:mb-12">
            <h2 class="text-2xl md:text-3xl font-bold text-primary mb-2 md:mb-4">{{ $sectionsArray['featured_properties']->title ?? 'Featured Apartments' }}</h2>
            <p class="text-gray-600 text-sm md:text-base">{{ $sectionsArray['featured_properties']->subtitle ?? 'Handpicked properties for your perfect stay' }}</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
            @forelse($featuredProperties as $property)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                <div class="relative h-48">
                    @if($property->primaryImage)
                    <img src="{{ $property->primaryImage->image_path }}" alt="{{ $property->title }}" class="w-full h-full object-cover">
                    @else
                    <div class="w-full h-full bg-gray-300 flex items-center justify-center">
                        <i class="fas fa-home text-4xl text-gray-400"></i>
                    </div>
                    @endif
                    @if($property->is_featured)
                    <span class="absolute top-4 left-4 bg-accent text-white px-3 py-1 text-sm font-bold rounded">Featured</span>
                    @endif
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-2">{{ $property->title }}</h3>
                    <p class="text-gray-600 text-sm mb-4"><i class="fas fa-map-marker-alt mr-2"></i>{{ $property->city }}, {{ $property->state }}</p>
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-2xl font-bold text-primary">₦{{ number_format($property->price_per_night) }}</span>
                            <span class="text-gray-500 text-sm">/night</span>
                        </div>
                        <a href="{{ route('properties.show', $property->slug) }}" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90 transition">View Details</a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-3 text-center py-8">
                <p class="text-gray-500">No featured properties available at the moment.</p>
            </div>
            @endforelse
        </div>
        
        <div class="text-center mt-12">
            <a href="{{ route('properties.index') }}" class="inline-block px-8 py-3 bg-accent text-white font-bold rounded-lg hover:bg-accent/90 transition">View All Apartments</a>
        </div>
        @endif
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

@if(isset($sectionsArray['reviews']) && $sectionsArray['reviews']->is_visible)
<section class="py-8 md:py-16 bg-gray-50">
    <div class="container mx-auto px-4 md:px-6">
        <div class="text-center mb-6 md:mb-12">
            <h2 class="text-2xl md:text-3xl font-bold text-primary mb-2 md:mb-4">{{ $sectionsArray['reviews']->title ?? 'What Our Guests Say' }}</h2>
            <p class="text-gray-600 text-sm md:text-base">{{ $sectionsArray['reviews']->subtitle ?? 'Reviews from our satisfied customers' }}</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-{{ count($testimonials) === 4 ? '2 lg:grid-cols-4' : '3' }} gap-8">
            @foreach($testimonials as $testimonial)
            <div class="bg-white p-6 rounded-lg">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center font-bold">
                        {{ substr($testimonial['name'], 0, 1) }}
                    </div>
                    <div class="ml-4">
                        <h4 class="font-bold">{{ $testimonial['name'] }}</h4>
                        <p class="text-sm text-gray-500">Guest</p>
                    </div>
                </div>
                <i class="fas fa-quote-left text-accent mb-2"></i>
                <p class="text-gray-600">{{ $testimonial['comment'] }}</p>
            </div>
            @endforeach

            @if(!$testimonials)
            @forelse($recentReviews as $review)
            <div class="bg-gray-50 p-6 rounded-lg">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center font-bold">
                        {{ substr($review->user->name, 0, 1) }}
                    </div>
                    <div class="ml-4">
                        <h4 class="font-bold">{{ $review->user->name }}</h4>
                        <p class="text-sm text-gray-500">{{ $review->property->title }}</p>
                    </div>
                </div>
                <div class="flex text-accent mb-2">
                    @for($i = 1; $i <= 5; $i++)
                    <i class="fas fa-star{{ $i <= $review->overall_rating ? '' : '-o' }}"></i>
                    @endfor
                </div>
                <p class="text-gray-600">{{ Str::limit($review->comment, 150) }}</p>
            </div>
            @empty
            <div class="col-span-3 text-center py-8">
                <p class="text-gray-500">No reviews yet.</p>
            </div>
            @endforelse
            @endif
        </div>
    </div>
</section>
@endif

@if(isset($sectionsArray['cta']) && $sectionsArray['cta']->is_visible)
<section class="py-16 bg-primary text-white">
    <div class="container mx-auto px-6 text-center">
        <h2 class="text-3xl font-bold mb-4">{{ $sectionsArray['cta']->title ?? 'Ready to Book Your Stay?' }}</h2>
        <p class="text-xl mb-8">{{ $sectionsArray['cta']->subtitle ?? 'Stay. Relax. Connect.' }}</p>
        <a href="{{ url($sectionsArray['cta']->button_link ?: route('properties.index')) }}" class="inline-block px-8 py-3 bg-accent text-white font-bold rounded-lg hover:bg-accent/90 transition">{{ $sectionsArray['cta']->button_text ?? 'Browse Apartments' }}</a>
    </div>
</section>
@endif

@if(isset($sectionsArray['amenities']) && $sectionsArray['amenities']->is_visible)
<section class="py-16 bg-background">
    <div class="container mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-primary mb-4">{{ $sectionsArray['amenities']->title ?? 'Our Amenities' }}</h2>
            <p class="text-gray-600">{{ $sectionsArray['amenities']->subtitle ?? 'Everything you need for a comfortable stay' }}</p>
        </div>
        
        @if($facilities)
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @foreach($facilities as $facility)
            <div class="bg-white p-6 rounded-lg text-center shadow">
                <i class="fas {{ $facility['icon'] ?? 'fa-check' }} text-3xl text-accent mb-3"></i>
                <p class="font-bold mb-2">{{ $facility['title'] }}</p>
                <p class="text-gray-600 text-sm">{{ $facility['description'] }}</p>
            </div>
            @endforeach
        </div>
        @else
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($amenities->flatten() as $amenity)
            <div class="bg-white p-4 rounded-lg text-center shadow">
                <i class="fas {{ $amenity->icon }} text-3xl text-accent mb-2"></i>
                <p class="font-medium">{{ $amenity->name }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif
@endsection
