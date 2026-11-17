@extends('layouts.admin')

@section('title', 'Homepage Sections')

@section('header', 'Homepage Sections')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <p class="text-gray-600 mb-4">Manage the content displayed on the homepage. Click edit to modify each section.</p>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($sections as $section)
        <div class="border rounded-lg p-4 hover:shadow-lg transition {{ $section->is_visible ? '' : 'opacity-60 bg-gray-50' }}">
            <div class="flex justify-between items-start mb-2">
                <span class="text-xs font-bold uppercase text-gray-500">{{ $section->section_key }}</span>
                @if($section->is_visible)
                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Visible</span>
                @else
                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">Hidden</span>
                @endif
            </div>
            <h3 class="font-bold text-lg mb-1">{{ $section->title }}</h3>
            @if($section->subtitle)
            <p class="text-sm text-gray-600 mb-3">{{ $section->subtitle }}</p>
            @endif
            <div class="flex justify-between items-center mt-4">
                <span class="text-xs text-gray-400">Order: {{ $section->order }}</span>
                <a href="{{ route('admin.homepage-sections.edit', $section->id) }}" class="px-3 py-1 bg-primary text-white text-sm rounded hover:bg-primary/90">Edit</a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
