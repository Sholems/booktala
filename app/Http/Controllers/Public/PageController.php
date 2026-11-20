<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PageSubmitContactRequest;
use App\Models\Blog;
use App\Models\Page;
use App\Models\Property;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    public function about()
    {
        return view('public.pages.about');
    }

    public function contact()
    {
        return view('public.pages.contact');
    }

    public function submitContact(PageSubmitContactRequest $request)
    {
        $validated = $request->validated();

        // For now we persist contact enquiries in logs to ensure no messages are lost.
        Log::info('Website contact form submission', $validated);

        return redirect()
            ->back()
            ->with('success', 'Thank you for contacting us. We will get back to you shortly.');
    }

    public function faq()
    {
        $page = Page::where('slug', 'faq')->first();

        return $page ? view('public.pages.show', compact('page')) : view('public.pages.faq');
    }

    public function terms()
    {
        $page = Page::where('slug', 'terms-and-conditions')->first();

        return $page ? view('public.pages.show', compact('page')) : view('public.pages.terms');
    }

    public function privacy()
    {
        $page = Page::where('slug', 'privacy-policy')->first();

        return $page ? view('public.pages.show', compact('page')) : view('public.pages.privacy');
    }

    public function cancellation()
    {
        $page = Page::where('slug', 'cancellation-policy')->first();

        return $page ? view('public.pages.show', compact('page')) : view('public.pages.cancellation');
    }

    public function show($slug)
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        return view('public.pages.show', compact('page'));
    }

    public function sitemap()
    {
        $properties = Property::active()->with('images')->orderBy('updated_at', 'desc')->get();
        $pages = Page::where('is_published', true)->orderBy('updated_at', 'desc')->get();
        $blogs = Blog::where('is_published', true)->whereNotNull('published_at')->orderBy('published_at', 'desc')->get();

        return response()->view('public.sitemap', compact('properties', 'pages', 'blogs'))
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }

    public function robots()
    {
        return response()->view('public.robots')->header('Content-Type', 'text/plain');
    }

    public function llmsTxt()
    {
        $path = public_path('llms.txt');
        if (! file_exists($path)) {
            abort(404);
        }

        return response(file_get_contents($path), 200)
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
