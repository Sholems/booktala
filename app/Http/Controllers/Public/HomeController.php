<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Blog;
use App\Models\HomepageSection;
use App\Models\Property;
use App\Models\Review;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProperties = Property::active()
            ->featured()
            ->with(['images', 'primaryImage'])
            ->take(6)
            ->get();

        $recentReviews = Review::with(['user', 'property'])
            ->where('is_published', true)
            ->latest()
            ->take(6)
            ->get();

        $amenities = Amenity::all()->groupBy('category');

        $recentBlogs = Blog::published()
            ->latest()
            ->take(3)
            ->get();

        $sections = HomepageSection::orderBy('order')->get();

        return view('public.home', compact('featuredProperties', 'recentReviews', 'amenities', 'recentBlogs', 'sections'));
    }
}
