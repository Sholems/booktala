@extends('layouts.admin')
@section('page_title', 'Edit Page')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.pages.index') }}" class="text-primary hover:underline">&larr; Back to Pages</a>
</div>

<form method="POST" action="{{ route('admin.pages.update', $page->id) }}" class="bg-white rounded-lg shadow p-6">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium mb-2">Title</label>
            <input type="text" name="title" value="{{ $page->title }}" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-2">Slug</label>
            <input type="text" name="slug" value="{{ $page->slug }}" class="w-full border rounded px-3 py-2" required>
        </div>
    </div>
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Content</label>
        <div class="bg-white">
            <div id="editor" class="rich-editor"></div>
            <input type="hidden" name="content" id="content" value="{{ htmlspecialchars($page->content) }}">
        </div>
    </div>
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Meta Title</label>
        <input type="text" name="meta_title" value="{{ $page->meta_title }}" class="w-full border rounded px-3 py-2">
    </div>
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Meta Description</label>
        <textarea name="meta_description" class="w-full border rounded px-3 py-2" rows="2">{{ $page->meta_description }}</textarea>
    </div>
    <div class="mt-6">
        <label class="flex items-center">
            <input type="checkbox" name="is_published" value="1" class="mr-2" {{ $page->is_published ? 'checked' : '' }}>
            <span class="text-sm font-medium">Publish</span>
        </label>
    </div>
    <div class="mt-6">
        <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-primary/90" onclick="document.getElementById('content').value = quill.root.innerHTML">Update Page</button>
    </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.quill = new Quill('#editor', {
        theme: 'snow',
        height: 300,
        placeholder: 'Enter content here...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'color': [] }, { 'background': [] }],
                ['link', 'image'],
                ['clean']
            ]
        }
    });
    quill.root.innerHTML = document.getElementById('content').value;
});
</script>
@endsection
