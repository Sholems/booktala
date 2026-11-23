@extends('layouts.app')

@php
    $primaryImageUrl = $property->images->first()?->image_path ? asset($property->images->first()->image_path) : asset('images/og-default.jpg');
    $seoDescription = (string) ($property->meta_description ?: $property->short_description ?: ('Book ' . $property->title . ' in ' . ($property->city ?? 'Lagos') . '. Luxury serviced shortlet apartment with 24/7 power, high-speed WiFi, air conditioning, and top-tier security. Direct rates from ₦' . number_format($property->price_per_night) . '/night.'));
    $pageTitle = $property->meta_title ?: ($property->title . ' - Luxury Shortlet in ' . ($property->city ?? 'Lagos') . ' | BookTala');
@endphp

@section('title', $pageTitle)
@section('meta_description', $seoDescription)
@section('meta_keywords', (string) $property->meta_keywords)

@section('og_title', $pageTitle)
@section('og_description', $seoDescription)
@section('og_image', $primaryImageUrl)
@section('og_type', 'place')

@section('twitter_title', $pageTitle)
@section('twitter_description', $seoDescription)
@section('twitter_image', $primaryImageUrl)

@push('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@graph": [
    {
      "@type": ["Apartment", "Accommodation"],
      "@id": "{{ url()->current() }}#apartment",
      "name": "{{ $property->title }}",
      "description": {{ json_encode($seoDescription) }},
      "url": "{{ url()->current() }}",
      "image": [
        @foreach($property->images as $img)
        "{{ $img->image_path }}"@if(!$loop->last),@endif
        @endforeach
      ],
      "numberOfRooms": {{ $property->bedrooms ?? 1 }},
      "numberOfBedrooms": {{ $property->bedrooms ?? 1 }},
      "numberOfBathroomsTotal": {{ $property->bathrooms ?? 1 }},
      "occupancy": {
        "@type": "QuantitativeValue",
        "maxValue": {{ $property->max_guests ?? 2 }}
      },
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "{{ $property->address ?? 'Plot 14, Admiralty Way, Lekki Phase 1' }}",
        "addressLocality": "{{ $property->city ?? 'Lekki Phase 1' }}",
        "addressRegion": "{{ $property->state ?? 'Lagos' }}",
        "addressCountry": "NG"
      },
      @if($property->latitude && $property->longitude)
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": {{ (float) $property->latitude }},
        "longitude": {{ (float) $property->longitude }}
      },
      @endif
      "offers": {
        "@type": "Offer",
        "price": "{{ (float) $property->price_per_night }}",
        "priceCurrency": "NGN",
        "availability": "https://schema.org/InStock",
        "url": "{{ url()->current() }}",
        "validFrom": "{{ now()->toIso8601String() }}"
      },
      "amenityFeature": [
        { "@type": "LocationFeatureSpecification", "name": "24/7 Uninterrupted Power Supply", "value": true },
        { "@type": "LocationFeatureSpecification", "name": "High-Speed Fiber WiFi", "value": true },
        { "@type": "LocationFeatureSpecification", "name": "Air Conditioning", "value": true },
        { "@type": "LocationFeatureSpecification", "name": "24/7 Gated Security & CCTV", "value": true }
        @foreach($property->amenities as $amenity)
        ,{ "@type": "LocationFeatureSpecification", "name": "{{ $amenity->name }}", "value": true }
        @endforeach
      ]
      @if($property->review_count > 0)
      ,"aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "{{ number_format($property->average_rating, 1) }}",
        "reviewCount": {{ $property->review_count }},
        "bestRating": "5",
        "worstRating": "1"
      }
      @if($property->reviews && $property->reviews->where('is_published', true)->count() > 0)
      ,"review": [
        @foreach($property->reviews->where('is_published', true)->take(5) as $rev)
        {
          "@type": "Review",
          "reviewRating": {
            "@type": "Rating",
            "ratingValue": "{{ $rev->overall_rating ?? 5 }}",
            "bestRating": "5"
          },
          "author": {
            "@type": "Person",
            "name": "{{ $rev->user->name ?? 'Verified Guest' }}"
          },
          "reviewBody": {{ json_encode($rev->comment ?? '') }}
        }@if(!$loop->last),@endif
        @endforeach
      ]
      @endif
      @endif
    },
    {
      "@type": "BreadcrumbList",
      "@id": "{{ url()->current() }}#breadcrumb",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "Home",
          "item": "{{ route('home') }}"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "name": "Apartments",
          "item": "{{ route('properties.index') }}"
        },
        {
          "@type": "ListItem",
          "position": 3,
          "name": "{{ $property->title }}",
          "item": "{{ url()->current() }}"
        }
      ]
    }
  ]
}
</script>
@endpush

@push('styles')
<style>
    .image-gallery-main { height: 400px; object-fit: cover; }
    .thumbnail.active { border: 3px solid var(--color-primary); }
    .calendar-day { width: 36px; height: 36px; }
    .calendar-day.disabled { background-color: #fee2e2; color: #9ca3af; cursor: not-allowed; }
    .calendar-day.available:hover { background-color: #dbeafe; cursor: pointer; }
    .calendar-day.check-in-date { background-color: var(--color-primary); color: white; }
    .calendar-day.check-out-date { background-color: var(--color-accent); color: white; }
    .calendar-day.in-range { background-color: #dbeafe; color: var(--color-primary); }
</style>
@endpush

@section('content')
<section class="py-4 md:py-8">
    <div class="container mx-auto px-4 md:px-6">
        <nav class="text-xs md:text-sm text-gray-600 mb-3 md:mb-4">
            <a href="{{ route('home') }}" class="hover:text-primary">Home</a>
            <span class="mx-1 md:mx-2">/</span>
            <a href="{{ route('properties.index') }}" class="hover:text-primary">Apartments</a>
            <span class="mx-1 md:mx-2">/</span>
            <span class="text-primary truncate">{{ $property->title }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-8">
            <div class="lg:col-span-2">
                <div class="mb-4 md:mb-6">
                    <div class="relative bg-gray-200 rounded-2xl overflow-hidden mb-3 md:mb-4 group cursor-pointer" onclick="openGalleryModal(0)">
                        @if($property->images->first())
                        <img src="{{ $property->images->first()->image_path }}" id="mainImage" alt="{{ $property->title }}" class="w-full h-52 md:h-96 object-cover group-hover:scale-[1.02] transition duration-300">
                        <div class="absolute bottom-3 right-3 bg-black/70 hover:bg-black/90 backdrop-blur-sm text-white text-xs font-semibold px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition">
                            <i class="fas fa-images"></i>
                            <span>View all {{ $property->images->count() }} photos</span>
                        </div>
                        @else
                        <div class="w-full h-52 md:h-96 bg-gray-300 flex items-center justify-center">
                            <i class="fas fa-home text-4xl md:text-6xl text-gray-400"></i>
                        </div>
                        @endif
                    </div>
                    <div class="flex gap-2 overflow-x-auto pb-2 custom-scrollbar">
                        @forelse($property->images as $idx => $image)
                        <img src="{{ $image->image_path }}" onclick="setMainImage('{{ $image->image_path }}', {{ $idx }})" 
                             class="w-16 md:w-24 h-12 md:h-20 object-cover rounded-lg cursor-pointer thumbnail {{ $image->is_primary ? 'active' : '' }}" data-index="{{ $idx }}">
                        @empty
                        @endforelse
                    </div>
                </div>

                <div class="mb-4 md:mb-8">
                    <h1 class="text-xl md:text-3xl font-bold mb-1 md:mb-2">{{ $property->title }}</h1>
                    <p class="text-gray-600 text-sm"><i class="fas fa-map-marker-alt mr-1 md:mr-2"></i>{{ $property->address }}, {{ $property->city }}, {{ $property->state }}</p>
                </div>

                <div class="flex gap-3 md:gap-6 mb-4 md:mb-8 text-center overflow-x-auto">
                    <div class="bg-gray-100 p-4 rounded-lg flex-1">
                        <i class="fas fa-bed text-2xl text-primary mb-2"></i>
                        <p class="font-bold">{{ $property->bedrooms }}</p>
                        <p class="text-sm text-gray-600">Bedrooms</p>
                    </div>
                    <div class="bg-gray-100 p-4 rounded-lg flex-1">
                        <i class="fas fa-bath text-2xl text-primary mb-2"></i>
                        <p class="font-bold">{{ $property->bathrooms }}</p>
                        <p class="text-sm text-gray-600">Bathrooms</p>
                    </div>
                    <div class="bg-gray-100 p-4 rounded-lg flex-1">
                        <i class="fas fa-user text-2xl text-primary mb-2"></i>
                        <p class="font-bold">{{ $property->max_guests }}</p>
                        <p class="text-sm text-gray-600">Guests</p>
                    </div>
                    <div class="bg-gray-100 p-4 rounded-lg flex-1">
                        <i class="fas fa-toilet text-2xl text-primary mb-2"></i>
                        <p class="font-bold">{{ $property->toilets }}</p>
                        <p class="text-sm text-gray-600">Toilets</p>
                    </div>
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">About This Property</h2>
                    <p class="text-gray-700 leading-relaxed">{{ $property->description }}</p>
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">Amenities</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @forelse($property->amenities as $amenity)
                        <div class="flex items-center gap-2">
                            <i class="fas {{ $amenity->icon }} text-accent"></i>
                            <span>{{ $amenity->name }}</span>
                        </div>
                        @empty
                        <p class="text-gray-500">No amenities listed</p>
                        @endforelse
                    </div>
                </div>

                <!-- House Rules & Stay Requirements -->
                <div class="mb-8 p-6 rounded-2xl bg-white border border-gray-200 shadow-sm">
                    <h2 class="text-xl md:text-2xl font-bold mb-4 text-gray-900 flex items-center gap-2.5">
                        <i class="fas fa-clipboard-list text-primary"></i> House Rules &amp; Stay Requirements
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 mb-4">
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-200/80 flex items-start gap-2.5">
                            <span class="text-red-500 text-lg mt-0.5"><i class="fas fa-ban-smoking"></i></span>
                            <div class="text-xs">
                                <span class="font-bold text-gray-900 block">No Indoor Smoking</span>
                                <span class="text-gray-500">Balcony smoking only</span>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-200/80 flex items-start gap-2.5">
                            <span class="text-amber-500 text-lg mt-0.5"><i class="fas fa-glass-cheers"></i></span>
                            <div class="text-xs">
                                <span class="font-bold text-gray-900 block">No Parties or Events</span>
                                <span class="text-gray-500">Quiet hours: 10 PM – 7 AM</span>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-200/80 flex items-start gap-2.5">
                            <span class="text-primary text-lg mt-0.5"><i class="fas fa-id-card"></i></span>
                            <div class="text-xs">
                                <span class="font-bold text-gray-900 block">Valid ID Required</span>
                                <span class="text-gray-500">Government ID at check-in</span>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-200/80 flex items-start gap-2.5">
                            <span class="text-primary text-lg mt-0.5"><i class="fas fa-users"></i></span>
                            <div class="text-xs">
                                <span class="font-bold text-gray-900 block">Max {{ $property->max_guests }} Guests</span>
                                <span class="text-gray-500">Occupancy strictly enforced</span>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-200/80 flex items-start gap-2.5">
                            <span class="text-emerald-500 text-lg mt-0.5"><i class="fas fa-clock"></i></span>
                            <div class="text-xs">
                                <span class="font-bold text-gray-900 block">Check-in: {{ $property->check_in_time ?? '2:00 PM' }}</span>
                                <span class="text-gray-500">Check-out: {{ $property->check_out_time ?? '11:00 AM' }}</span>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-200/80 flex items-start gap-2.5">
                            <span class="text-gray-500 text-lg mt-0.5"><i class="fas fa-paw"></i></span>
                            <div class="text-xs">
                                <span class="font-bold text-gray-900 block">No Pets</span>
                                <span class="text-gray-500">Notice required for service dogs</span>
                            </div>
                        </div>
                    </div>
                    @if($property->house_rules)
                    <div class="pt-3 border-t border-gray-100 text-sm text-gray-600 whitespace-pre-line leading-relaxed">
                        {{ $property->house_rules }}
                    </div>
                    @endif
                </div>

                <!-- Cancellation & Refund Policy -->
                <div class="mb-8 p-6 rounded-2xl bg-white border border-gray-200 shadow-sm">
                    <h2 class="text-xl md:text-2xl font-bold mb-4 text-gray-900 flex items-center gap-2.5">
                        <i class="fas fa-shield-alt text-primary"></i> Cancellation &amp; Refund Policy
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4 text-xs">
                        <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200">
                            <span class="font-bold text-emerald-950 block text-sm mb-1">Full Refund</span>
                            <p class="text-emerald-800">Cancel at least 7 days before check-in for a 100% refund of nightly rate and caution fee.</p>
                        </div>
                        <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200">
                            <span class="font-bold text-amber-950 block text-sm mb-1">50% Refund</span>
                            <p class="text-amber-800">Cancel between 48 hours and 7 days before check-in for 50% accommodation refund + 100% caution fee.</p>
                        </div>
                        <div class="p-3.5 rounded-xl bg-red-50/70 border border-red-200">
                            <span class="font-bold text-red-950 block text-sm mb-1">Non-Refundable</span>
                            <p class="text-red-800">Cancellations under 48 hours are non-refundable. Caution fee is refunded in full.</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">Processing fee (₦{{ number_format(config('booking.processing_fee', 2000)) }}) covers payment handling and is non-refundable. Read our full <a href="{{ route('pages.cancellation') }}" target="_blank" class="text-primary font-semibold underline">Cancellation Terms</a>.</p>
                    @if($property->cancellation_policy)
                    <div class="mt-3 pt-3 border-t border-gray-100 text-sm text-gray-600 whitespace-pre-line leading-relaxed">
                        {{ $property->cancellation_policy }}
                    </div>
                    @endif
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">Location</h2>
                    @php
                        $hasCoordinates = !empty($property->latitude) && !empty($property->longitude);
                        $mapQuery = trim(implode(', ', array_filter([
                            $property->address,
                            $property->city,
                            $property->state,
                            $property->country,
                        ])));

                        $embedTarget = $hasCoordinates
                            ? $property->latitude . ',' . $property->longitude
                            : $mapQuery;

                        $mapsEmbedUrl = 'https://maps.google.com/maps?output=embed&q=' . urlencode($embedTarget) . '&z=15';
                        $mapsOpenUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($embedTarget);
                    @endphp

                    <div class="rounded-lg overflow-hidden border border-gray-200 bg-gray-100">
                        <iframe
                            src="{{ $mapsEmbedUrl }}"
                            title="Map for {{ $property->title }}"
                            class="w-full h-64 md:h-80"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen>
                        </iframe>
                    </div>
                    <div class="mt-3">
                        <a href="{{ $mapsOpenUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:text-accent">
                            <i class="fas fa-map-marker-alt"></i>
                            Open in Google Maps
                        </a>
                    </div>
                </div>

                @if($property->reviews->count() > 0)
                <div class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">Reviews</h2>
                    <div class="space-y-4">
                        @forelse($property->reviews as $review)
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-bold">
                                    {{ substr($review->user->name, 0, 1) }}
                                </div>
                                <div class="ml-3">
                                    <h4 class="font-bold">{{ $review->user->name }}</h4>
                                    <div class="text-accent text-sm">
                                        @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star{{ $i <= $review->rating ? '' : '-o' }}"></i>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                            <p class="text-gray-700">{{ $review->comment }}</p>
                        </div>
                        @empty
                        @endforelse
                    </div>
                </div>
                @endif
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-lg sticky top-24">
                    <div class="text-center mb-6">
                        <span class="text-3xl font-bold text-primary">₦{{ number_format($property->price_per_night) }}</span>
                        <span class="text-gray-500">/night</span>
                    </div>

                    @if($property->price_per_week || $property->price_per_month)
                    <div class="mb-4 text-sm">
                        @if($property->price_per_week)
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Weekly</span>
                            <span class="font-bold">₦{{ number_format($property->price_per_week) }}</span>
                        </div>
                        @endif
                        @if($property->price_per_month)
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Monthly</span>
                            <span class="font-bold">₦{{ number_format($property->price_per_month) }}</span>
                        </div>
                        @endif
                    </div>
                    @endif

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm mb-2">Select Dates</label>
                        <div id="availability-calendar" class="border rounded-lg p-3 bg-gray-50">
                            <div class="flex justify-between items-center mb-3">
                                <button type="button" id="prev-month" class="p-2 hover:bg-gray-200 rounded"><i class="fas fa-chevron-left"></i></button>
                                <span id="calendar-month" class="font-semibold"></span>
                                <button type="button" id="next-month" class="p-2 hover:bg-gray-200 rounded"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-center text-xs mb-2">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>
                            <div id="calendar-days" class="grid grid-cols-7 gap-1"></div>
                        </div>
                        <input type="hidden" id="check_in" name="check_in">
                        <input type="hidden" id="check_out" name="check_out">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm mb-2">Guests</label>
                        <select name="guests" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                            @for($i = 1; $i <= $property->max_guests; $i++)
                            <option value="{{ $i }}">{{ $i }} Guest{{ $i > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                    </div>

                    <div id="price-summary" class="hidden mb-4 p-3 bg-gray-50 rounded-lg">
                        <div class="flex justify-between text-sm mb-1">
                            <span>Price per night</span>
                            <span>₦{{ number_format($property->price_per_night) }}</span>
                        </div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>Number of nights</span>
                            <span id="nights-count">0</span>
                        </div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>VAT ({{ rtrim(rtrim(number_format((float) \App\Models\Setting::get('vat_rate', 7.5), 2, '.', ''), '0'), '.') }}%)</span>
                            <span id="tax-amount">₦0</span>
                        </div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>Processing fee <span class="text-xs text-gray-400 font-normal">(non-refundable)</span></span>
                            <span id="processing-fee">₦{{ number_format((float) config('booking.processing_fee')) }}</span>
                        </div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>Caution deposit <span class="text-xs text-emerald-600 font-semibold">(100% refundable)</span></span>
                            <span id="caution-fee">₦{{ number_format((float) config('booking.caution_fee')) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-lg border-t pt-2 mt-2">
                            <span>Total</span>
                            <span id="total-price" class="text-primary font-black">₦0</span>
                        </div>
                    </div>

                    <button type="button" id="check-availability-btn" class="w-full bg-accent text-white py-3.5 rounded-xl font-bold hover:bg-accent/90 shadow-md shadow-accent/20 transition transform active:scale-[0.99] flex items-center justify-center gap-2">
                        <span>Book Now</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </button>

                    <a href="https://wa.me/2349116110242?text={{ urlencode('Hi BookTala Concierge, I have an enquiry about ' . $property->title . ':') }}" target="_blank" class="w-full mt-2.5 inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border border-emerald-500 text-emerald-700 bg-emerald-50/50 hover:bg-emerald-100 font-semibold text-xs md:text-sm transition">
                        <i class="fab fa-whatsapp text-emerald-600 text-base"></i>
                        <span>Ask Host on WhatsApp</span>
                    </a>

                    <div class="mt-6 text-sm text-gray-600">
                        <div class="flex justify-between py-2">
                            <span>Check-in</span>
                            <span class="font-medium">{{ $property->check_in_time }}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span>Check-out</span>
                            <span class="font-medium">{{ $property->check_out_time }}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span>Min. nights</span>
                            <span class="font-medium">{{ $property->min_nights }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($similarProperties->count() > 0)
        <div class="mt-12">
            <h2 class="text-2xl font-bold mb-6">Similar Properties</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($similarProperties as $similar)
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="h-48 bg-gray-300">
                        @if($similar->primaryImage)
                        <img src="{{ $similar->primaryImage->image_path }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold mb-2">{{ $similar->title }}</h3>
                        <p class="text-gray-600 text-sm mb-2">{{ $similar->city }}</p>
                        <span class="text-primary font-bold">₦{{ number_format($similar->price_per_night) }}/night</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Mobile Sticky Bottom Booking Bar -->
    <div class="lg:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-gray-200 px-4 py-3 z-40 flex items-center justify-between shadow-2xl">
        <div>
            <span class="text-[11px] text-gray-500 block leading-tight">Price per night</span>
            <span class="text-base font-black text-primary">₦{{ number_format($property->price_per_night) }}</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="https://wa.me/2349116110242?text={{ urlencode('Hi BookTala Concierge, enquiry for ' . $property->title) }}" target="_blank" class="w-10 h-10 rounded-xl border border-emerald-500 text-emerald-600 bg-emerald-50 flex items-center justify-center text-lg shrink-0" title="Chat on WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <button type="button" onclick="document.getElementById('availability-calendar').scrollIntoView({ behavior: 'smooth', block: 'center' })" class="px-5 py-2.5 bg-accent hover:bg-accent/90 text-white font-bold text-sm rounded-xl shadow-md shadow-accent/20 transition">
                Check Dates
            </button>
        </div>
    </div>

    <!-- Photo Lightbox Modal -->
    <div id="gallery-lightbox" class="fixed inset-0 z-50 hidden bg-black/95 backdrop-blur-sm flex flex-col justify-between p-4 md:p-6 animate-in fade-in duration-150">
        <div class="flex items-center justify-between text-white z-10">
            <div class="flex items-center gap-3">
                <span class="font-bold text-sm md:text-base">{{ $property->title }}</span>
                <span id="gallery-counter" class="text-xs bg-white/20 px-2.5 py-1 rounded-full text-white/90"></span>
            </div>
            <button type="button" onclick="closeGalleryModal()" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-lg transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="relative flex-1 flex items-center justify-center my-4 overflow-hidden">
            <button type="button" onclick="prevGalleryImage()" class="absolute left-2 md:left-6 w-11 h-11 rounded-full bg-black/50 hover:bg-black/80 text-white flex items-center justify-center text-lg transition z-10">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <img id="lightbox-image" src="" alt="{{ $property->title }}" class="max-h-[75vh] max-w-[90vw] object-contain rounded-xl shadow-2xl transition duration-200">
            
            <button type="button" onclick="nextGalleryImage()" class="absolute right-2 md:right-6 w-11 h-11 rounded-full bg-black/50 hover:bg-black/80 text-white flex items-center justify-center text-lg transition z-10">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <div class="flex justify-center gap-2 overflow-x-auto py-2 max-w-2xl mx-auto custom-scrollbar">
            @foreach($property->images as $idx => $img)
            <img src="{{ $img->image_path }}" onclick="showGalleryImage({{ $idx }})" class="lightbox-thumb w-14 h-10 object-cover rounded cursor-pointer border-2 border-transparent opacity-60 hover:opacity-100 transition" data-index="{{ $idx }}">
            @endforeach
        </div>
    </div>
</section>

@push('scripts')
<script>
const unavailableDates = @json($unavailableDates);
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let checkInDate = null;
let checkOutDate = null;

function renderCalendar() {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('calendar-month').textContent = monthNames[currentMonth] + ' ' + currentYear;
    
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    let html = '';
    for (let i = 0; i < firstDay; i++) {
        html += '<div></div>';
    }
    
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(currentYear, currentMonth, day);
        const dateStr = date.toISOString().split('T')[0];
        const isPast = date < today;
        const isUnavailable = unavailableDates.includes(dateStr);
        const isDisabled = isPast || isUnavailable;
        
        let classes = 'calendar-day flex items-center justify-center text-sm rounded ';
        
        if (checkInDate && checkOutDate && dateStr > checkInDate && dateStr < checkOutDate) {
            classes += ' in-range';
        } else if (isDisabled) {
            classes += 'disabled';
        } else {
            classes += 'available';
        }
        
        if (checkInDate === dateStr) {
            classes += ' check-in-date';
        }
        if (checkOutDate === dateStr) {
            classes += ' check-out-date';
        }
        
        html += `<div class="${classes}" data-date="${dateStr}" ${isDisabled ? '' : 'onclick="selectDate(this)"'}>${day}</div>`;
    }
    
    document.getElementById('calendar-days').innerHTML = html;
}

function selectDate(el) {
    const dateStr = el.dataset.date;
    
    if (!checkInDate || (checkInDate && checkOutDate)) {
        checkInDate = dateStr;
        checkOutDate = null;
        document.getElementById('check_in').value = dateStr;
        document.getElementById('check_out').value = '';
    } else if (dateStr > checkInDate) {
        const isRangeClear = unavailableDates.some(d => d > checkInDate && d < dateStr);
        if (!isRangeClear) {
            checkOutDate = dateStr;
            document.getElementById('check_out').value = dateStr;
        }
    } else {
        checkInDate = dateStr;
        checkOutDate = null;
        document.getElementById('check_in').value = dateStr;
        document.getElementById('check_out').value = '';
    }
    
    updatePriceSummary();
    renderCalendar();
}

function updatePriceSummary() {
    const pricePerNight = {{ $property->price_per_night }};
    const processingFee = {{ (float) config('booking.processing_fee') }};
    const cautionFee = {{ (float) config('booking.caution_fee') }};
    const summaryEl = document.getElementById('price-summary');
    const nightsEl = document.getElementById('nights-count');
    const taxEl = document.getElementById('tax-amount');
    const totalEl = document.getElementById('total-price');
    
    if (checkInDate && checkOutDate) {
        const start = new Date(checkInDate);
        const end = new Date(checkOutDate);
        const nights = Math.round((end - start) / (1000 * 60 * 60 * 24));
        
        if (nights > 0) {
            const subtotal = nights * pricePerNight;
            const vatRate = {{ (float) \App\Models\Setting::get('vat_rate', 7.5) }};
            const fallbackTax = subtotal * (vatRate / 100);
            const fallbackTotal = subtotal + fallbackTax + processingFee + cautionFee;
            nightsEl.textContent = nights;
            taxEl.textContent = '₦' + fallbackTax.toLocaleString(undefined, { maximumFractionDigits: 2 });
            totalEl.textContent = '₦' + fallbackTotal.toLocaleString(undefined, { maximumFractionDigits: 2 });
            summaryEl.classList.remove('hidden');

            const params = new URLSearchParams({
                check_in: checkInDate,
                check_out: checkOutDate,
                property_id: '{{ $property->id }}'
            });
            fetch('{{ route("availability.check") }}?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.available && data.total_price !== null) {
                        taxEl.textContent = '₦' + Number(data.tax_amount || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });
                        totalEl.textContent = '₦' + Number(data.total_price).toLocaleString(undefined, { maximumFractionDigits: 2 });
                        document.querySelector('[id="nights-count"]').textContent = data.nights;
                    }
                })
                .catch(() => {});
        } else {
            summaryEl.classList.add('hidden');
        }
    } else {
        summaryEl.classList.add('hidden');
    }
}

document.getElementById('prev-month').addEventListener('click', function() {
    currentMonth--;
    if (currentMonth < 0) { currentMonth = 11; currentYear--; }
    renderCalendar();
});

document.getElementById('next-month').addEventListener('click', function() {
    currentMonth++;
    if (currentMonth > 11) { currentMonth = 0; currentYear++; }
    renderCalendar();
});

document.getElementById('check-availability-btn').addEventListener('click', function() {
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    const guests = document.querySelector('select[name="guests"]').value;
    
    if (!checkIn || !checkOut) {
        alert('Please select check-in and check-out dates');
        return;
    }
    
    window.location.href = '{{ route("booking.show", $property->slug) }}?check_in=' + checkIn + '&check_out=' + checkOut + '&guests=' + guests;
});

renderCalendar();

// Gallery Lightbox
const propertyImages = @json($property->images->pluck('image_path')->values());
let currentGalleryIndex = 0;

function setMainImage(src, index) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
        if (i === index) thumb.classList.add('active');
        else thumb.classList.remove('active');
    });
}

function openGalleryModal(index = 0) {
    if (!propertyImages || propertyImages.length === 0) return;
    document.getElementById('gallery-lightbox').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    showGalleryImage(index);
}

function closeGalleryModal() {
    document.getElementById('gallery-lightbox').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function showGalleryImage(index) {
    if (!propertyImages || propertyImages.length === 0) return;
    if (index < 0) index = propertyImages.length - 1;
    if (index >= propertyImages.length) index = 0;
    currentGalleryIndex = index;

    document.getElementById('lightbox-image').src = propertyImages[currentGalleryIndex];
    document.getElementById('gallery-counter').textContent = `${currentGalleryIndex + 1} of ${propertyImages.length}`;

    document.querySelectorAll('.lightbox-thumb').forEach((thumb, i) => {
        if (i === currentGalleryIndex) {
            thumb.classList.add('border-white', 'opacity-100');
            thumb.classList.remove('opacity-60', 'border-transparent');
            thumb.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        } else {
            thumb.classList.remove('border-white', 'opacity-100');
            thumb.classList.add('opacity-60', 'border-transparent');
        }
    });
}

function prevGalleryImage() {
    showGalleryImage(currentGalleryIndex - 1);
}

function nextGalleryImage() {
    showGalleryImage(currentGalleryIndex + 1);
}

document.addEventListener('keydown', function(e) {
    const lightbox = document.getElementById('gallery-lightbox');
    if (!lightbox || lightbox.classList.contains('hidden')) return;
    if (e.key === 'Escape') closeGalleryModal();
    if (e.key === 'ArrowLeft') prevGalleryImage();
    if (e.key === 'ArrowRight') nextGalleryImage();
});
</script>
@endpush
@endsection
