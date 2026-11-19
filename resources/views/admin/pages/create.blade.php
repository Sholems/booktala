@extends('layouts.admin')
@section('title', 'Create New Page')
@section('page_title', 'Create Page')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.pages.index') }}" class="text-xs font-semibold text-primary hover:underline inline-flex items-center gap-1 mb-2">
                <i class="fas fa-arrow-left"></i> Back to All Pages
            </a>
            <h2 class="text-2xl font-bold text-gray-800">Create New Page</h2>
            <p class="text-xs text-gray-500 mt-0.5">Author a new policy, informational resource, or landing page for the website.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.pages.store') }}" id="page-form" class="space-y-6">
        @csrf

        <!-- Primary Information Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Page Title *</label>
                    <input type="text" name="title" id="page-title" value="{{ old('title') }}" placeholder="e.g. VIP Concierge Guidelines" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm font-semibold" required>
                    @error('title')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Slug (URL Segment) *</label>
                    <div class="flex items-center">
                        <span class="px-3 py-2.5 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg text-xs text-gray-500 font-mono">/</span>
                        <input type="text" name="slug" id="page-slug" value="{{ old('slug') }}" placeholder="vip-concierge-guidelines" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-r-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm font-mono" required>
                    </div>
                    @error('slug')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Content Editor -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Page Content & Body *</label>
                    <button type="button" id="toggle-editor-mode" class="text-xs text-primary hover:text-accent font-semibold flex items-center gap-1 transition">
                        <i class="fas fa-code"></i> <span id="toggle-label">Switch to HTML Source</span>
                    </button>
                </div>

                <!-- Visual Editor Container -->
                <div id="visual-editor-container" class="border border-gray-300 rounded-lg overflow-hidden bg-white">
                    <div id="editor" style="min-height: 400px; font-size: 15px;"></div>
                </div>

                <!-- Raw HTML Textarea (hidden by default) -->
                <div id="html-editor-container" class="hidden">
                    <textarea id="raw-html" rows="18" placeholder="<h2>Your Section Header</h2><p>Your detailed paragraph...</p>" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg font-mono text-xs leading-relaxed focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    <p class="text-[11px] text-gray-400 mt-1">Editing in raw HTML mode allows direct customization of headings, bullet lists, tables, and links.</p>
                </div>

                <!-- Hidden Input Sent to Controller -->
                <input type="hidden" name="content" id="content" value="{{ old('content') }}">
                @error('content')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- SEO Metadata Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-3 flex items-center gap-2">
                <i class="fas fa-search text-primary"></i> Search Engine Optimization (SEO)
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Meta Title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title') }}" placeholder="e.g. VIP Concierge Guidelines - BookTala" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
                    <p class="text-[11px] text-gray-400 mt-1">Recommended length: 50–60 characters.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Meta Description</label>
                    <textarea name="meta_description" rows="3" placeholder="Brief summary of this policy or page for search engines and social sharing..." class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">{{ old('meta_description') }}</textarea>
                    <p class="text-[11px] text-gray-400 mt-1">Recommended length: 140–160 characters.</p>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100">
                <label class="inline-flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_published" value="1" class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary" {{ old('is_published', true) ? 'checked' : '' }}>
                    <div>
                        <span class="text-sm font-bold text-gray-900">Publish immediately</span>
                        <p class="text-xs text-gray-500">Uncheck to save as a draft hidden from the public website.</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="flex items-center justify-between bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <a href="{{ route('admin.pages.index') }}" class="text-xs font-semibold text-gray-600 hover:text-gray-900">
                Cancel
            </a>
            <button type="submit" class="bg-primary hover:bg-primary/90 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                <i class="fas fa-plus-circle"></i> Create &amp; Save Page
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const titleInput = document.getElementById('page-title');
    const slugInput = document.getElementById('page-slug');
    const hiddenContent = document.getElementById('content');
    const rawHtmlTextarea = document.getElementById('raw-html');
    const visualContainer = document.getElementById('visual-editor-container');
    const htmlContainer = document.getElementById('html-editor-container');
    const toggleBtn = document.getElementById('toggle-editor-mode');
    const toggleLabel = document.getElementById('toggle-label');
    const form = document.getElementById('page-form');

    let isHtmlMode = false;

    // Auto-generate slug from title
    let userEditedSlug = false;
    slugInput.addEventListener('input', function() {
        userEditedSlug = true;
    });

    titleInput.addEventListener('input', function() {
        if (!userEditedSlug) {
            slugInput.value = titleInput.value
                .toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    });

    // Initialize Quill Editor
    const quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Write or paste detailed page content here...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, 4, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'color': [] }, { 'background': [] }],
                ['link'],
                ['clean']
            ]
        }
    });

    // Populate initial content if validation failed and had old content
    if (hiddenContent.value) {
        quill.root.innerHTML = hiddenContent.value;
        rawHtmlTextarea.value = hiddenContent.value;
    }

    // Toggle between Visual & HTML mode
    toggleBtn.addEventListener('click', function() {
        if (!isHtmlMode) {
            rawHtmlTextarea.value = quill.root.innerHTML;
            visualContainer.classList.add('hidden');
            htmlContainer.classList.remove('hidden');
            toggleLabel.textContent = 'Switch to Visual Editor';
            isHtmlMode = true;
        } else {
            quill.root.innerHTML = rawHtmlTextarea.value;
            htmlContainer.classList.add('hidden');
            visualContainer.classList.remove('hidden');
            toggleLabel.textContent = 'Switch to HTML Source';
            isHtmlMode = false;
        }
    });

    // Form submit handler: ensure hiddenContent has the latest content
    form.addEventListener('submit', function () {
        if (isHtmlMode) {
            hiddenContent.value = rawHtmlTextarea.value;
        } else {
            hiddenContent.value = quill.root.innerHTML;
        }
    });
});
</script>
@endpush
@endsection
