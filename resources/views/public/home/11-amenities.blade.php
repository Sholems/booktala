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
