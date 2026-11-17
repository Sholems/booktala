@extends('layouts.admin')
@section('page_title', 'Create Page')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.pages.index') }}" class="text-primary hover:underline">&larr; Back to Pages</a>
</div>

<form method="POST" action="{{ route('admin.pages.store') }}" class="bg-white rounded-lg shadow p-6">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium mb-2">Title</label>
            <input type="text" name="title" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-2">Slug</label>
            <input type="text" name="slug" class="w-full border rounded px-3 py-2" required>
        </div>
    </div>
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Content</label>
        <div class="bg-white">
            <div id="editor" class="rich-editor"></div>
            <input type="hidden" name="content" id="content">
        </div>
    </div>
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Meta Title</label>
        <input type="text" name="meta_title" class="w-full border rounded px-3 py-2">
    </div>
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Meta Description</label>
        <textarea name="meta_description" class="w-full border rounded px-3 py-2" rows="2"></textarea>
    </div>
    <div class="mt-6">
        <label class="flex items-center">
            <input type="checkbox" name="is_published" value="1" class="mr-2" checked>
            <span class="text-sm font-medium">Publish</span>
        </label>
    </div>
    <div class="mt-6">
        <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-primary/90" onclick="document.getElementById('content').value = quill.root.innerHTML">Create Page</button>
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
    });
</script>
@endsection
