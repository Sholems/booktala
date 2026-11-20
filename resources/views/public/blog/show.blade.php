@extends('layouts.app')

@php
    $blogImageUrl = $blog->image
        ? (\Illuminate\Support\Str::startsWith($blog->image, ['http://', 'https://']) ? $blog->image : url('media/' . ltrim($blog->image, '/')))
        : asset('images/og-default.jpg');
    $blogExcerpt = (string) ($blog->meta_description ?: $blog->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($blog->content), 155));
    $blogTitle = $blog->meta_title ?: ($blog->title . ' - BookTala Blog');
@endphp

@section('title', $blogTitle)
@section('meta_description', $blogExcerpt)

@section('og_title', $blogTitle)
@section('og_description', $blogExcerpt)
@section('og_image', $blogImageUrl)
@section('og_type', 'article')

@section('twitter_title', $blogTitle)
@section('twitter_description', $blogExcerpt)
@section('twitter_image', $blogImageUrl)

@section('extra_meta')
    <meta property="article:published_time" content="{{ $blog->published_at ? $blog->published_at->toIso8601String() : $blog->created_at->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $blog->updated_at->toIso8601String() }}">
    <meta property="article:author" content="{{ $blog->author->name ?? 'BookTala Editorial' }}">
    @if($blog->category)
    <meta property="article:section" content="{{ $blog->category }}">
    @endif
@endsection

@push('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": {{ json_encode($blog->title) }},
  "description": {{ json_encode($blogExcerpt) }},
  "image": "{{ $blogImageUrl }}",
  "datePublished": "{{ $blog->published_at ? $blog->published_at->toIso8601String() : $blog->created_at->toIso8601String() }}",
  "dateModified": "{{ $blog->updated_at->toIso8601String() }}",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "{{ url()->current() }}"
  },
  "speakable": {
    "@type": "SpeakableSpecification",
    "cssSelector": [".blog-headline", ".blog-summary"]
  },
  "author": {
    "@type": "Person",
    "name": "{{ $blog->author->name ?? 'BookTala Editorial' }}"
  },
  "publisher": {
    "@type": "Organization",
    "name": "BookTala",
    "logo": {
      "@type": "ImageObject",
      "url": "{{ asset('images/booktala-logo.png') }}"
    }
  }
}
</script>
@endpush

@section('content')
<section class="py-12 bg-primary text-white">
    <div class="container mx-auto px-6">
        <nav class="text-sm text-gray-300 mb-4">
            <a href="{{ route('home') }}" class="hover:text-white">Home</a>
            <span class="mx-2">/</span>
            <a href="{{ route('blog.index') }}" class="hover:text-white">Blog</a>
            <span class="mx-2">/</span>
            <span class="text-white">{{ $blog->title }}</span>
        </nav>
        <h1 class="blog-headline text-4xl font-bold">{{ $blog->title }}</h1>
    </div>
</section>

<section class="py-12">
    <div class="container mx-auto px-6">
        <div class="max-w-4xl mx-auto">
            @if($blog->image)
            <img src="{{ \Illuminate\Support\Str::startsWith($blog->image, ['http://', 'https://']) ? $blog->image : url('media/' . ltrim($blog->image, '/')) }}" alt="{{ $blog->title }}" class="w-full h-96 object-cover rounded-lg mb-8">
            @endif
            
            <div class="flex justify-between items-center text-gray-500 mb-8 pb-8 border-b">
                <div>
                    <span class="font-medium">{{ $blog->author->name ?? 'Admin' }}</span>
                    @if($blog->category)
                    <span class="mx-2">|</span>
                    <span class="text-accent">{{ $blog->category }}</span>
                    @endif
                </div>
                <div>{{ $blog->published_at->format('F d, Y') }}</div>
            </div>
            
            <div class="prose max-w-none">
                {!! \App\Support\SafeHtml::clean($blog->content) !!}
            </div>
            
            <div class="mt-12 pt-8 border-t">
                <a href="{{ route('blog.index') }}" class="text-primary hover:underline">← Back to Blog</a>
            </div>
        </div>
    </div>
</section>
@endsection
