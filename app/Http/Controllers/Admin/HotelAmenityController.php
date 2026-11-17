<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelAmenity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HotelAmenityController extends Controller
{
    public function index()
    {
        $amenities = HotelAmenity::orderBy('category')->orderBy('name')->get();
        return view('admin.hotel-amenities.index', compact('amenities'));
    }

    public function create()
    {
        return view('admin.hotel-amenities.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
        ]);

        HotelAmenity::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'icon' => $request->icon,
            'description' => $request->description,
            'category' => $request->category,
            'location' => $request->location,
            'opening_time' => $request->opening_time,
            'closing_time' => $request->closing_time,
            'is_active' => $request->is_active ?? true,
            'is_premium' => $request->is_premium ?? false,
        ]);

        return redirect()->route('admin.hotel-amenities.index')->with('success', 'Hotel amenity created');
    }

    public function edit(HotelAmenity $hotelAmenity)
    {
        return view('admin.hotel-amenities.edit', compact('hotelAmenity'));
    }

    public function update(Request $request, HotelAmenity $hotelAmenity)
    {
        $hotelAmenity->update($request->all());
        return redirect()->route('admin.hotel-amenities.index')->with('success', 'Hotel amenity updated');
    }

    public function destroy(HotelAmenity $hotelAmenity)
    {
        $hotelAmenity->delete();
        return redirect()->route('admin.hotel-amenities.index')->with('success', 'Hotel amenity deleted');
    }
}