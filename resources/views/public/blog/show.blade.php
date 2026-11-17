@extends('layouts.app')

@section('title', $blog->meta_title ?: $blog->title . ' - BookTala Blog')
@section('meta_description', $blog->meta_description)

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
        <h1 class="text-4xl font-bold">{{ $blog->title }}</h1>
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
                {!! $blog->content !!}
            </div>
            
            <div class="mt-12 pt-8 border-t">
                <a href="{{ route('blog.index') }}" class="text-primary hover:underline">← Back to Blog</a>
            </div>
        </div>
    </div>
</section>
@endsection
