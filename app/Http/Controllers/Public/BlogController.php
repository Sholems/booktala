<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Blog;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::published()
            ->latest()
            ->paginate(9);

        return view('public.blog.index', compact('blogs'));
    }

    public function show($slug)
    {
        // Drafts and scheduled posts are not public.
        $blog = Blog::published()->where('slug', $slug)->firstOrFail();

        return view('public.blog.show', compact('blog'));
    }
}
