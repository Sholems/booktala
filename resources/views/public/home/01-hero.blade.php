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
    {{-- Uniform solid color overlay with 90% opacity (no gradient) --}}
    <div class="absolute inset-0 bg-primary/90" style="background-color: rgba(11, 45, 91, 0.90);"></div>
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
