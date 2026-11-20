{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    {{-- Homepage --}}
    <url>
        <loc>{{ route('home') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    {{-- Apartments Catalog --}}
    <url>
        <loc>{{ route('properties.index') }}</loc>
        <lastmod>{{ $properties->max('updated_at')?->toAtomString() ?? now()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    {{-- Static Public Pages --}}
    <url>
        <loc>{{ route('pages.about') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc>{{ route('pages.contact') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc>{{ route('pages.faq') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>{{ route('pages.terms') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ route('pages.privacy') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ route('pages.cancellation') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ route('blog.index') }}</loc>
        <lastmod>{{ $blogs->max('published_at')?->toAtomString() ?? now()->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>

    {{-- Individual Active Apartments with Image Extensions --}}
    @foreach($properties as $property)
    <url>
        <loc>{{ route('properties.show', $property->slug ?: $property->id) }}</loc>
        <lastmod>{{ $property->updated_at?->toAtomString() ?? now()->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
        @foreach($property->images as $img)
        <image:image>
            <image:loc>{{ $img->image_path }}</image:loc>
            <image:title>{{ htmlspecialchars($property->title, ENT_XML1, 'UTF-8') }}</image:title>
            <image:caption>{{ htmlspecialchars($property->short_description ?? $property->title, ENT_XML1, 'UTF-8') }}</image:caption>
        </image:image>
        @endforeach
    </url>
    @endforeach

    {{-- Published Blog Posts --}}
    @foreach($blogs as $blog)
    <url>
        <loc>{{ route('blog.show', $blog->slug ?: $blog->id) }}</loc>
        <lastmod>{{ $blog->updated_at?->toAtomString() ?? $blog->published_at?->toAtomString() ?? now()->toAtomString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
        @if($blog->image)
        <image:image>
            <image:loc>{{ \Illuminate\Support\Str::startsWith($blog->image, ['http://', 'https://']) ? $blog->image : url('media/' . ltrim($blog->image, '/')) }}</image:loc>
            <image:title>{{ htmlspecialchars($blog->title, ENT_XML1, 'UTF-8') }}</image:title>
        </image:image>
        @endif
    </url>
    @endforeach

    {{-- Custom Published CMS Pages --}}
    @foreach($pages as $page)
        @if(!in_array($page->slug, ['about', 'contact', 'faq', 'terms-and-conditions', 'privacy-policy', 'cancellation-policy']))
        <url>
            <loc>{{ route('pages.show', $page->slug) }}</loc>
            <lastmod>{{ $page->updated_at?->toAtomString() ?? now()->toAtomString() }}</lastmod>
            <changefreq>monthly</changefreq>
            <priority>0.5</priority>
        </url>
        @endif
    @endforeach

</urlset>
