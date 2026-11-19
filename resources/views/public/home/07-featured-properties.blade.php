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
