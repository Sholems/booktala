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
