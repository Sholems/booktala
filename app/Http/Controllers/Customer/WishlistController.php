<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = Wishlist::where('user_id', auth()->id())
            ->with('property.images')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('customer.wishlist.index', compact('wishlists'));
    }

    public function store(Property $property)
    {
        Wishlist::firstOrCreate([
            'user_id' => auth()->id(),
            'property_id' => $property->id
        ]);
        
        return back()->with('success', 'Added to wishlist');
    }

    public function destroy(Property $property)
    {
        Wishlist::where('user_id', auth()->id())
            ->where('property_id', $property->id)
            ->delete();
        
        return back()->with('success', 'Removed from wishlist');
    }
}
