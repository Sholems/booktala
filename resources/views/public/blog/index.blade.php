@extends('layouts.app')

@php
    $blogMetaTitle = 'Travel Guides & Shortlet Living in Lagos - BookTala Blog';
    $blogMetaDesc = 'Discover local neighborhood guides, serviced shortlet insights, and executive travel tips across Lekki Phase 1, Victoria Island, and Ikoyi with BookTala.';
@endphp

@section('title', $blogMetaTitle)
@section('meta_description', $blogMetaDesc)

@section('og_title', $blogMetaTitle)
@section('og_description', $blogMetaDesc)
@section('og_type', 'website')

@push('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": {{ json_encode($blogMetaTitle) }},
  "description": {{ json_encode($blogMetaDesc) }},
  "url": "{{ url()->current() }}",
  "mainEntity": {
    "@type": "ItemList",
    "itemListElement": [
      @foreach($blogs as $idx => $b)
      {
        "@type": "ListItem",
        "position": {{ $idx + 1 }},
        "url": "{{ route('blog.show', $b->slug ?: $b->id) }}",
        "name": {{ json_encode($b->title) }}
      }@if(!$loop->last),@endif
      @endforeach
    ]
  }
}
</script>
@endpush

@section('content')
<section class="py-12 bg-primary text-white">
    <div class="container mx-auto px-6">
        <h1 class="text-4xl font-bold mb-2">Blog</h1>
        <p class="text-xl">Latest news and travel tips</p>
    </div>
</section>

<section class="py-16">
    <div class="container mx-auto px-6">
        @if($blogs->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($blogs as $blog)
            <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                <div class="h-48 bg-gray-300">
                    @if($blog->image)
                    <img src="{{ \Illuminate\Support\Str::startsWith($blog->image, ['http://', 'https://']) ? $blog->image : url('media/' . ltrim($blog->image, '/')) }}" alt="{{ $blog->title }}" class="w-full h-full object-cover" loading="lazy" decoding="async">
                    @endif
                </div>
                <div class="p-6">
                    @if($blog->category)
                    <span class="text-xs font-bold text-accent uppercase">{{ $blog->category }}</span>
                    @endif
                    <h3 class="text-xl font-bold mb-2 mt-2">
                        <a href="{{ route('blog.show', $blog->slug) }}" class="hover:text-primary">{{ $blog->title }}</a>
                    </h3>
                    <p class="text-gray-600 mb-4">{{ $blog->excerpt }}</p>
                    <div class="flex justify-between items-center text-sm text-gray-500">
                        <span>{{ $blog->author->name ?? 'Admin' }}</span>
                        <span>{{ $blog->published_at->format('M d, Y') }}</span>
                    </div>
                    <a href="{{ route('blog.show', $blog->slug) }}" class="text-primary hover:underline mt-4 inline-block">Read More →</a>
                </div>
            </div>
            @endforeach
        </div>
        
        <div class="mt-8">{{ $blogs->links() }}</div>
        @else
        <div class="text-center py-12">
            <p class="text-gray-500">No blog posts available yet.</p>
        </div>
        @endif
    </div>
</section>
@endsection
