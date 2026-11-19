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
