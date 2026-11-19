<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogStoreRequest;
use App\Http\Requests\Admin\BlogUpdateRequest;
use App\Models\Blog;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::with('author')->latest()->paginate(20);

        return view('admin.blogs.index', compact('blogs'));
    }

    public function create()
    {
        return view('admin.blogs.create');
    }

    public function store(BlogStoreRequest $request)
    {
        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['title']);
        $validated['author_id'] = auth()->id();
        $validated['is_published'] = $request->has('is_published');
        $validated['published_at'] = $request->has('is_published') ? now() : null;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('blogs', 'public');
        }

        Blog::create($validated);

        return redirect()->route('admin.blogs.index')->with('success', 'Blog post created');
    }

    public function edit(Blog $blog)
    {
        return view('admin.blogs.edit', compact('blog'));
    }

    public function update(BlogUpdateRequest $request, Blog $blog)
    {
        $validated = $request->validated();

        if ($request->title !== $blog->title) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['is_published'] = $request->has('is_published');

        if ($request->has('is_published') && ! $blog->is_published) {
            $validated['published_at'] = now();
        } elseif (! $request->has('is_published')) {
            $validated['published_at'] = null;
        }

        if ($request->hasFile('image')) {
            if ($blog->image) {
                \Storage::disk('public')->delete($blog->image);
            }
            $validated['image'] = $request->file('image')->store('blogs', 'public');
        }

        $blog->update($validated);

        return back()->with('success', 'Blog post updated');
    }

    public function destroy(Blog $blog)
    {
        if ($blog->image) {
            \Storage::disk('public')->delete($blog->image);
        }
        $blog->delete();

        return back()->with('success', 'Blog post deleted');
    }
}
