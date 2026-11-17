@extends('layouts.admin')

@section('title', 'Edit Homepage Section')

@section('header', 'Edit: ' . $homepageSection->title)

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <form action="{{ route('admin.homepage-sections.update', $homepageSection->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-gray-700 text-sm mb-2">Section Key</label>
                <input type="text" value="{{ $homepageSection->section_key }}" class="w-full px-4 py-2 border rounded bg-gray-100" disabled>
                <p class="text-xs text-gray-500 mt-1">The unique identifier for this section</p>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm mb-2">Display Order</label>
                <input type="number" name="order" value="{{ $homepageSection->order }}" class="w-full px-4 py-2 border rounded">
            </div>
        </div>
        
        <div class="mt-6">
            <label class="block text-gray-700 text-sm mb-2">Title</label>
            <input type="text" name="title" value="{{ $homepageSection->title }}" class="w-full px-4 py-2 border rounded">
        </div>
        
        <div class="mt-6">
            <label class="block text-gray-700 text-sm mb-2">Subtitle</label>
            <textarea name="subtitle" rows="2" class="w-full px-4 py-2 border rounded">{{ $homepageSection->subtitle }}</textarea>
        </div>
        
        <div class="mt-6">
            <label class="block text-gray-700 text-sm mb-2">Content</label>
            <textarea name="content" rows="4" class="w-full px-4 py-2 border rounded">{{ $homepageSection->content }}</textarea>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label class="block text-gray-700 text-sm mb-2">Image URL</label>
                <input type="text" name="image" value="{{ $homepageSection->image }}" class="w-full px-4 py-2 border rounded">
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm mb-2">Button Text</label>
                <input type="text" name="button_text" value="{{ $homepageSection->button_text }}" class="w-full px-4 py-2 border rounded">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label class="block text-gray-700 text-sm mb-2">Button Link</label>
                <input type="text" name="button_link" value="{{ $homepageSection->button_link }}" class="w-full px-4 py-2 border rounded">
            </div>
            
            <div class="flex items-center mt-6">
                <input type="checkbox" name="is_visible" id="is_visible" value="1" {{ $homepageSection->is_visible ? 'checked' : '' }} class="mr-2">
                <label for="is_visible" class="text-gray-700">Show this section on homepage</label>
            </div>
        </div>
        
        <div class="mt-8 flex gap-4">
            <button type="submit" class="px-6 py-2 bg-primary text-white rounded hover:bg-primary/90">Save Changes</button>
            <a href="{{ route('admin.homepage-sections.index') }}" class="px-6 py-2 border rounded hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
