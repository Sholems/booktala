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
