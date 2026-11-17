<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Property;
use Illuminate\Http\Request;
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
    
    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

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
        $properties = Property::active()->get();
        $pages = Page::where('is_published', true)->get();
        return response()->view('public.sitemap', compact('properties', 'pages'))->header('Content-Type', 'text/xml');
    }
    
    public function robots()
    {
        return response()->view('public.robots')->header('Content-Type', 'text/plain');
    }
}
