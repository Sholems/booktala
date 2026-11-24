<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Page;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoGeoAeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_xml_returns_valid_xml_with_properties_pages_and_blogs(): void
    {
        $property = Property::create([
            'title' => 'Luxury Lekki Oceanview Penthouse',
            'slug' => 'luxury-lekki-oceanview-penthouse',
            'description' => 'A prime waterfront serviced apartment in Lekki Phase 1.',
            'address' => '12 Admiralty Way, Lekki Phase 1',
            'city' => 'Lekki',
            'state' => 'Lagos',
            'price_per_night' => 125000,
            'max_guests' => 4,
            'min_nights' => 1,
            'max_nights' => 30,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 2,
            'status' => 'active',
        ]);

        PropertyImage::create([
            'property_id' => $property->id,
            'image_path' => 'properties/oceanview-1.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $inactiveProperty = Property::create([
            'title' => 'Hidden Secret Villa',
            'slug' => 'hidden-secret-villa',
            'description' => 'Not active yet.',
            'address' => 'Unpublished road',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'price_per_night' => 50000,
            'max_guests' => 2,
            'min_nights' => 1,
            'max_nights' => 10,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'toilets' => 1,
            'status' => 'inactive',
        ]);

        $page = Page::create([
            'title' => 'Concierge and Chauffeur Services',
            'slug' => 'concierge-services',
            'content' => 'Full service details.',
            'is_published' => true,
        ]);

        $blog = Blog::create([
            'title' => 'The Ultimate Guide to Shortlet Stays in Victoria Island',
            'slug' => 'ultimate-guide-shortlets-vi',
            'content' => 'Comprehensive insider guide to staying in Victoria Island, Lagos.',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $draftBlog = Blog::create([
            'title' => 'Draft Blog Post',
            'slug' => 'draft-blog-post',
            'content' => 'Draft post.',
            'is_published' => false,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('text/xml', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', $content);
        $this->assertStringContainsString(route('properties.show', $property->slug), $content);
        $this->assertStringContainsString('<image:image>', $content);
        $this->assertStringContainsString(route('pages.show', $page->slug), $content);
        $this->assertStringContainsString(route('blog.show', $blog->slug), $content);

        // Inactive / draft items must be excluded
        $this->assertStringNotContainsString(route('properties.show', $inactiveProperty->slug), $content);
        $this->assertStringNotContainsString(route('blog.show', $draftBlog->slug), $content);
    }

    public function test_robots_txt_returns_proper_crawler_directives_and_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /admin/', $content);
        $this->assertStringContainsString('Disallow: /customer/', $content);
        $this->assertStringContainsString('Disallow: /staff/', $content);
        $this->assertStringContainsString('User-agent: GPTBot', $content);
        $this->assertStringContainsString('User-agent: PerplexityBot', $content);
        $this->assertStringContainsString('User-agent: ClaudeBot', $content);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $content);
    }

    public function test_llms_txt_endpoint_serves_markdown_grounding_document(): void
    {
        $response = $this->get('/llms.txt');

        $response->assertOk();
        $this->assertStringContainsString('text/markdown', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        $this->assertStringContainsString('# BookTala', $content);
        $this->assertStringContainsString('G-Briel Property Ltd', $content);
        $this->assertStringContainsString('## Canonical URLs', $content);
        $this->assertStringContainsString('## Core Value Propositions & Guarantees', $content);
    }

    public function test_homepage_contains_global_schema_and_og_metadata(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('<meta property="og:site_name" content="BookTala"', $content);
        $this->assertStringContainsString('"@type": "Organization"', $content);
        $this->assertStringContainsString('"@type": "WebSite"', $content);
        $this->assertStringContainsString('"@type": "SearchAction"', $content);
        $this->assertStringContainsString('"@type": "LodgingBusiness"', $content);
        $this->assertStringContainsString('G-Briel Property Ltd', $content);
        $this->assertStringContainsString('<meta name="geo.region" content="NG-LA">', $content);
        $this->assertStringContainsString('<meta name="ICBM" content="6.4474, 3.4723">', $content);
    }

    public function test_apartment_page_contains_accommodation_schema_breadcrumbs_and_og_tags(): void
    {
        $property = Property::create([
            'title' => 'Signature 3-Bedroom Ikoyi Suite',
            'slug' => 'signature-3-bedroom-ikoyi-suite',
            'description' => 'Ultra-luxury high-rise apartment in Old Ikoyi with private pool and 24/7 power.',
            'address' => '5 Glover Road, Ikoyi',
            'city' => 'Ikoyi',
            'state' => 'Lagos',
            'price_per_night' => 250000,
            'max_guests' => 6,
            'min_nights' => 2,
            'max_nights' => 60,
            'bedrooms' => 3,
            'bathrooms' => 3,
            'toilets' => 4,
            'status' => 'active',
        ]);

        $response = $this->get(route('properties.show', $property->slug));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('<meta property="og:title"', $content);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $content);
        $this->assertStringContainsString('"@type": ["Apartment", "Accommodation"]', $content);
        $this->assertStringContainsString('"@type": "BreadcrumbList"', $content);
        $this->assertStringContainsString('Signature 3-Bedroom Ikoyi Suite', $content);
    }

    public function test_faq_page_contains_faqpage_schema_and_concise_aeo_answers(): void
    {
        $response = $this->get(route('pages.faq'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('"@type": "FAQPage"', $content);
        $this->assertStringContainsString('"@type": "Question"', $content);
        $this->assertStringContainsString('"@type": "Answer"', $content);
        $this->assertStringContainsString('How do I book an apartment with BookTala?', $content);
        $this->assertStringContainsString('Is 24/7 electricity guaranteed in BookTala apartments?', $content);
        $this->assertStringContainsString('What payment methods are supported?', $content);
    }

    public function test_blog_post_contains_blogposting_schema_and_article_meta(): void
    {
        $blog = Blog::create([
            'title' => 'Top 10 Benefits of Direct Booking vs OTAs in 2026',
            'slug' => 'top-10-direct-booking-benefits',
            'excerpt' => 'Why booking directly with BookTala provides zero hidden markups and personalized concierge service.',
            'content' => '<p>Direct booking on BookTala guarantees best rate parity, complimentary flexible check-in when available, and instant WhatsApp concierge support.</p>',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blog.show', $blog->slug));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('<meta property="og:type" content="article">', $content);
        $this->assertStringContainsString('<meta property="article:published_time"', $content);
        $this->assertStringContainsString('"@type": "BlogPosting"', $content);
        $this->assertStringContainsString('Top 10 Benefits of Direct Booking vs OTAs in 2026', $content);
    }

    public function test_properties_catalog_contains_collection_schema_and_dynamic_geo_title(): void
    {
        $property = Property::create([
            'title' => 'The Admiralty Presidential Shortlet',
            'slug' => 'admiralty-presidential-shortlet',
            'description' => 'Serviced shortlet in Lekki.',
            'address' => 'Plot 10 Admiralty Way',
            'city' => 'Lekki',
            'state' => 'Lagos',
            'price_per_night' => 180000,
            'max_guests' => 4,
            'min_nights' => 1,
            'max_nights' => 30,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 2,
            'status' => 'active',
        ]);

        $response = $this->get(route('properties.index'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('"@type": "CollectionPage"', $content);
        $this->assertStringContainsString('"@type": "ItemList"', $content);
        $this->assertStringContainsString('The Admiralty Presidential Shortlet', $content);
    }

    public function test_properties_catalog_handles_city_and_type_filter_seo_metadata(): void
    {
        $response = $this->get(route('properties.index', ['city' => 'Lekki', 'property_type' => 'penthouse']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('Luxury Penthouses in Lekki - BookTala', $content);
        $this->assertStringContainsString('Lekki, Lagos with 24/7 power', $content);
    }

    public function test_apartment_detail_renders_aggregaterating_and_reviews_when_present(): void
    {
        $user = User::factory()->create(['name' => 'Dr. Babatunde Cole']);
        $property = Property::create([
            'title' => 'Banana Island Executive Waterfront Villa',
            'slug' => 'banana-island-executive-waterfront-villa',
            'description' => 'Ultra high-end waterfront villa.',
            'address' => 'Ocean Drive, Banana Island',
            'city' => 'Ikoyi',
            'state' => 'Lagos',
            'price_per_night' => 450000,
            'max_guests' => 8,
            'min_nights' => 3,
            'max_nights' => 90,
            'bedrooms' => 4,
            'bathrooms' => 4,
            'toilets' => 5,
            'status' => 'active',
        ]);

        Review::create([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'cleanliness_rating' => 5,
            'location_rating' => 5,
            'value_rating' => 5,
            'communication_rating' => 5,
            'overall_rating' => 5,
            'comment' => 'Exceptional hospitality and flawless 24/7 power backup.',
            'is_published' => true,
        ]);

        $response = $this->get(route('properties.show', $property->slug));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('"@type": "AggregateRating"', $content);
        $this->assertStringContainsString('"ratingValue": "5.0"', $content);
        $this->assertStringContainsString('"reviewCount": 1', $content);
        $this->assertStringContainsString('Dr. Babatunde Cole', $content);
        $this->assertStringContainsString('Exceptional hospitality and flawless 24/7 power backup.', $content);
    }

    public function test_blog_index_contains_collectionpage_and_itemlist_schema(): void
    {
        Blog::create([
            'title' => 'Top Lagos Restaurants Near Lekki Shortlets',
            'slug' => 'top-lagos-restaurants-lekki',
            'excerpt' => 'Our curated culinary guide for guests.',
            'content' => 'Gourmet dining options.',
            'is_published' => true,
            'published_at' => now()->subDays(2),
        ]);

        $response = $this->get(route('blog.index'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('"@type": "CollectionPage"', $content);
        $this->assertStringContainsString('"@type": "ItemList"', $content);
        $this->assertStringContainsString('Top Lagos Restaurants Near Lekki Shortlets', $content);
    }

    public function test_faq_and_blog_pages_include_voice_search_speakable_specification(): void
    {
        $faqResponse = $this->get(route('pages.faq'));
        $faqResponse->assertOk();
        $this->assertStringContainsString('"@type": "SpeakableSpecification"', $faqResponse->getContent());
        $this->assertStringContainsString('.aeo-answer-capsule', $faqResponse->getContent());

        $blog = Blog::create([
            'title' => 'Voice Assistant Enabled Travel Tips',
            'slug' => 'voice-assistant-travel-tips',
            'excerpt' => 'A guide for modern voice-guided travel.',
            'content' => 'Content here.',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $blogResponse = $this->get(route('blog.show', $blog->slug));
        $blogResponse->assertOk();
        $this->assertStringContainsString('"@type": "SpeakableSpecification"', $blogResponse->getContent());
        $this->assertStringContainsString('.blog-headline', $blogResponse->getContent());
    }
}
