@extends('layouts.app')

@section('title', $page->meta_title ?? $page->title)

@section('content')
<div class="bg-white py-12">
    <div class="container mx-auto px-6 max-w-4xl">
        <nav class="flex items-center text-sm text-gray-500 mb-8">
            <a href="{{ route('home') }}" class="hover:text-primary">Home</a>
            <span class="mx-2">/</span>
            <span class="text-gray-800">{{ $page->title }}</span>
        </nav>
        
        <article class="prose prose-lg max-w-none">
            {!! $page->content !!}
        </article>
    </div>
</div>
@endsection
