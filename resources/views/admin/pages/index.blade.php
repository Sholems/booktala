@extends('layouts.admin')
@section('page_title', 'Pages')
@section('content')
<div class="mb-6 flex justify-between items-center">
    <h2 class="text-2xl font-bold">All Pages</h2>
    <a href="{{ route('admin.pages.create') }}" class="bg-primary text-white px-4 py-2 rounded">
        <i class="fas fa-plus mr-2"></i> Create Page
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($pages as $page)
            <tr>
                <td class="px-6 py-4">{{ $page->title }}</td>
                <td class="px-6 py-4 text-gray-500">{{ $page->slug }}</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded-full {{ $page->is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $page->is_published ? 'Published' : 'Draft' }}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <a href="{{ route('admin.pages.edit', $page->id) }}" class="text-blue-600 hover:underline mr-3">Edit</a>
                    <form action="{{ route('admin.pages.destroy', $page->id) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Delete this page?')">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-6 py-4 text-center text-gray-500">No pages found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $pages->links() }}</div>
@endsection
