@php
    $siteName = \App\Models\Setting::get('site_name', config('brand.name', 'BookTala'));
    $siteEmail = \App\Models\Setting::get('email', config('brand.email', 'concierge@booktala.com'));
    $sitePhone = \App\Models\Setting::get('phone', config('brand.phone', '+234 814 000 8252'));
    $siteAddress = \App\Models\Setting::get('address', config('brand.address', 'Plot 14, Admiralty Way, Lekki Phase 1, Lagos, Nigeria'));
    $companyName = \App\Models\Setting::get('company_name', config('brand.owner', 'G-Briel Property Ltd'));
    $logoUrl = asset('images/booktala-logo.png');
    $socials = array_values(array_filter([
        \App\Models\Setting::get('social_facebook', config('brand.social.facebook')),
        \App\Models\Setting::get('social_twitter', config('brand.social.twitter')),
        \App\Models\Setting::get('social_instagram', config('brand.social.instagram')),
        \App\Models\Setting::get('social_linkedin', 'https://linkedin.com/company/booktalastays'),
    ]));
@endphp
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "{{ url('/') }}#organization",
      "name": "{{ $siteName }}",
      "legalName": "{{ $companyName }}",
      "url": "{{ url('/') }}",
      "logo": {
        "@type": "ImageObject",
        "@id": "{{ url('/') }}#logo",
        "url": "{{ $logoUrl }}",
        "caption": "{{ $siteName }} Logo"
      },
      "image": "{{ $logoUrl }}",
      "telephone": "{{ $sitePhone }}",
      "email": "{{ $siteEmail }}",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Plot 14, Admiralty Way, Lekki Phase 1",
        "addressLocality": "Lekki Phase 1",
        "addressRegion": "Lagos",
        "addressCountry": "NG"
      },
      "sameAs": @json($socials)
    },
    {
      "@type": "WebSite",
      "@id": "{{ url('/') }}#website",
      "url": "{{ url('/') }}",
      "name": "{{ $siteName }} Luxury Stays & Shortlets",
      "description": "{{ $siteName }} - Luxury Serviced Short-Let Apartments in Lagos, Nigeria.",
      "publisher": {
        "@id": "{{ url('/') }}#organization"
      },
      "inLanguage": "en-NG",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "{{ url('/apartments') }}?city={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    },
    {
      "@type": "LodgingBusiness",
      "@id": "{{ url('/') }}#lodging",
      "name": "{{ $siteName }} Luxury Stays",
      "parentOrganization": {
        "@id": "{{ url('/') }}#organization"
      },
      "url": "{{ url('/') }}",
      "logo": "{{ $logoUrl }}",
      "image": "{{ asset('images/og-default.jpg') }}",
      "telephone": "{{ $sitePhone }}",
      "email": "{{ $siteEmail }}",
      "priceRange": "₦₦₦₦",
      "currenciesAccepted": "NGN, USD, GBP, EUR",
      "paymentAccepted": "Credit Card, Debit Card, Bank Transfer",
      "checkinTime": "14:00",
      "checkoutTime": "11:00",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Plot 14, Admiralty Way, Lekki Phase 1",
        "addressLocality": "Lekki Phase 1",
        "addressRegion": "Lagos",
        "postalCode": "105102",
        "addressCountry": "NG"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 6.4474,
        "longitude": 3.4723
      },
      "amenityFeature": [
        {
          "@type": "LocationFeatureSpecification",
          "name": "24/7 Uninterrupted Power Supply",
          "value": true
        },
        {
          "@type": "LocationFeatureSpecification",
          "name": "High-Speed Fiber-Optic WiFi",
          "value": true
        },
        {
          "@type": "LocationFeatureSpecification",
          "name": "24/7 Gated Security & CCTV",
          "value": true
        },
        {
          "@type": "LocationFeatureSpecification",
          "name": "Air Conditioning & Climate Control",
          "value": true
        },
        {
          "@type": "LocationFeatureSpecification",
          "name": "Smart HD TV with Netflix & Streaming",
          "value": true
        },
        {
          "@type": "LocationFeatureSpecification",
          "name": "Fully Equipped Chef Kitchen",
          "value": true
        }
      ]
    }
  ]
}
</script>
