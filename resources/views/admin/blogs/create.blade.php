@extends('layouts.admin')
@section('page_title', 'Create Blog Post')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.blogs.index') }}" class="text-primary hover:underline">&larr; Back to Blogs</a>
</div>

<form method="POST" action="{{ route('admin.blogs.store') }}" class="bg-white rounded-lg shadow p-6" enctype="multipart/form-data">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium mb-2">Title</label>
            <input type="text" name="title" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-2">Category</label>
            <input type="text" name="category" class="w-full border rounded px-3 py-2" placeholder="e.g., Travel, Tips">
        </div>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Featured Image</label>
        <input type="file" name="image" accept="image/*" class="w-full border rounded px-3 py-2">
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Excerpt</label>
        <textarea name="excerpt" class="w-full border rounded px-3 py-2" rows="2" placeholder="Short description..."></textarea>
    </div>
    
    <div class="mt-6">
        <label class="block text-sm font-medium mb-2">Content</label>
        <div class="bg-white">
            <div id="editor" class="rich-editor"></div>
            <input type="hidden" name="content" id="content">
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <div>
            <label class="block text-sm font-medium mb-2">Meta Title</label>
            <input type="text" name="meta_title" class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-2">Meta Description</label>
            <textarea name="meta_description" class="w-full border rounded px-3 py-2" rows="2"></textarea>
        </div>
    </div>
    
    <div class="mt-6">
        <label class="flex items-center">
            <input type="checkbox" name="is_published" value="1" class="mr-2">
            <span class="text-sm font-medium">Publish immediately</span>
        </label>
    </div>
    
    <div class="mt-6">
        <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-primary/90" onclick="document.getElementById('content').value = quill.root.innerHTML">Create Post</button>
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
