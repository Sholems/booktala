<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::with(['user', 'property'])->paginate(20);
        return view('admin.reviews.index', compact('reviews'));
    }

    public function show(Review $review)
    {
        $review->load(['user', 'property']);
        return view('admin.reviews.show', compact('review'));
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return back()->with('success', 'Review deleted');
    }

    public function approve(Review $review)
    {
        $review->update(['is_published' => true]);
        return back()->with('success', 'Review approved');
    }

    public function reply(Request $request, Review $review)
    {
        $review->update(['admin_reply' => $request->reply]);
        return back()->with('success', 'Reply added');
    }
}