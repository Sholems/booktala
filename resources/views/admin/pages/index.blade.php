@extends('layouts.admin')
@section('title', 'Website Pages & Policies')
@section('page_title', 'Website Pages')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Website Pages & Policies</h2>
            <p class="text-sm text-gray-500 mt-1">Manage, update, and publish legal terms, privacy policies, house rules, and informational pages.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.pages.create') }}" class="bg-primary hover:bg-primary/90 text-white font-semibold text-sm px-4 py-2.5 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                <i class="fas fa-plus"></i> Create New Page
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 bg-gray-50/50 border-b border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500">
            <span>Showing {{ $pages->total() }} published and draft pages</span>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Live</span>
                <span class="inline-flex items-center gap-1.5 ml-2"><span class="w-2 h-2 rounded-full bg-gray-400"></span> Draft</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Page Title</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">URL / Slug</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Last Updated</th>
                        <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($pages as $page)
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0 font-bold">
                                    <i class="fas fa-file-alt text-sm"></i>
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('admin.pages.edit', $page->id) }}" class="text-sm font-bold text-gray-900 hover:text-primary transition truncate block">
                                        {{ $page->title }}
                                    </a>
                                    @if($page->meta_title)
                                    <p class="text-xs text-gray-400 truncate max-w-xs">{{ $page->meta_title }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-xs">
                            <code class="px-2 py-1 rounded bg-slate-100 text-slate-700 font-mono">/{{ $page->slug }}</code>
                        </td>
                        <td class="px-6 py-4">
                            @if($page->is_published)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Published
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 mr-1.5"></span> Draft
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500">
                            {{ $page->updated_at->format('M d, Y · H:i') }}
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 text-xs font-medium">
                            <a href="{{ url($page->slug) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 transition" title="View live page on website">
                                <i class="fas fa-external-link-alt text-[10px]"></i> View
                            </a>
                            <a href="{{ route('admin.pages.edit', $page->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded bg-primary/10 hover:bg-primary/20 text-primary transition" title="Edit content & SEO">
                                <i class="fas fa-edit text-[10px]"></i> Edit
                            </a>
                            <form action="{{ route('admin.pages.destroy', $page->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this page?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded bg-red-50 hover:bg-red-100 text-red-600 transition" title="Delete page">
                                    <i class="fas fa-trash-alt text-[10px]"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">
                            <i class="fas fa-file-alt text-3xl mb-3 block text-gray-300"></i>
                            No pages found. Click "Create New Page" to add one.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $pages->links() }}</div>
</div>
@endsection
