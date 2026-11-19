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
