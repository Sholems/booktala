@extends('layouts.admin')
@section('page_title', 'Blog Posts')
@section('content')
<div class="mb-6 flex justify-between items-center">
    <h2 class="text-2xl font-bold">All Blog Posts</h2>
    <a href="{{ route('admin.blogs.create') }}" class="bg-primary text-white px-4 py-2 rounded">
        <i class="fas fa-plus mr-2"></i> Create Post
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($blogs as $blog)
            <tr>
                <td class="px-6 py-4">
                    @if($blog->image)
                    <img src="{{ url('media/' . ltrim($blog->image, '/')) }}" class="w-16 h-12 object-cover rounded">
                    @else
                    <div class="w-16 h-12 bg-gray-200 rounded flex items-center justify-center">
                        <i class="fas fa-image text-gray-400"></i>
                    </div>
                    @endif
                </td>
                <td class="px-6 py-4 font-medium">{{ $blog->title }}</td>
                <td class="px-6 py-4 text-gray-500">{{ $blog->category ?? 'Uncategorized' }}</td>
                <td class="px-6 py-4 text-gray-500">{{ $blog->author->name ?? 'Unknown' }}</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded-full {{ $blog->is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ $blog->is_published ? 'Published' : 'Draft' }}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <a href="{{ route('admin.blogs.edit', $blog->id) }}" class="text-blue-600 hover:underline mr-3">Edit</a>
                    <form action="{{ route('admin.blogs.destroy', $blog->id) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Delete this post?')">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No blog posts found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $blogs->links() }}</div>
@endsection
