<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\BlockedDate;
use App\Models\Reservation;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::active()->with(['images', 'primaryImage', 'amenities', 'reviews']);

        if ($request->filled('check_in') && $request->filled('check_out')) {
            $checkIn = $request->check_in;
            $checkOut = $request->check_out;
            $query->whereDoesntHave('reservations', function ($q) use ($checkIn, $checkOut) {
                $q->blockingAvailability()
                    ->where(function ($sub) use ($checkIn, $checkOut) {
                        $sub->where('check_in', '<', $checkOut)
                            ->where('check_out', '>', $checkIn);
                    });
            })->whereDoesntHave('blockedDates', function ($q) use ($checkIn, $checkOut) {
                $q->where(function ($sub) use ($checkIn, $checkOut) {
                    $sub->where('start_date', '<=', $checkOut)
                        ->where('end_date', '>=', $checkIn);
                });
            });
        }

        if ($request->has('city') && $request->city) {
            $location = '%'.$request->city.'%';
            $query->where(function ($q) use ($location) {
                $q->where('city', 'like', $location)
                    ->orWhere('state', 'like', $location)
                    ->orWhere('address', 'like', $location);
            });
        }

        if ($request->has('property_type') && $request->property_type && Schema::hasColumn('properties', 'property_type')) {
            $query->where('property_type', $request->property_type);
        }

        if ($request->has('guests') && $request->guests) {
            $query->where('max_guests', '>=', $request->guests);
        }

        if ($request->has('bedrooms') && $request->bedrooms) {
            $query->where('bedrooms', '>=', $request->bedrooms);
        }

        if ($request->has('bathrooms') && $request->bathrooms) {
            $query->where('bathrooms', '>=', $request->bathrooms);
        }

        if ($request->has('min_price') && $request->min_price) {
            $query->where('price_per_night', '>=', $request->min_price);
        }

        if ($request->has('max_price') && $request->max_price) {
            $query->where('price_per_night', '<=', $request->max_price);
        }

        if ($request->has('amenities') && $request->amenities) {
            $query->whereHas('amenities', function ($q) use ($request) {
                $q->whereIn('amenities.id', $request->amenities);
            });
        }

        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'price_low':
                $query->orderBy('price_per_night', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price_per_night', 'desc');
                break;
            case 'rating':
                $query->withCount('reviews')->orderBy('reviews_count', 'desc');
                break;
            default:
                $query->latest();
        }

        $properties = $query->paginate(12);
        $amenities = Amenity::all();

        return view('public.properties.index', compact('properties', 'amenities'));
    }

    public function show($slug)
    {
        $property = Unit::where('slug', $slug)
            ->with(['images', 'amenities', 'reviews' => function ($q) {
                $q->where('is_published', true)->with('user');
            }])
            ->firstOrFail();

        $unavailableDates = $this->getUnavailableDates($property->id);

        $similarProperties = Unit::active()
            ->where('id', '!=', $property->id)
            ->where('city', $property->city)
            ->with('primaryImage')
            ->take(3)
            ->get();

        return view('public.properties.show', compact('property', 'similarProperties', 'unavailableDates'));
    }

    private function getUnavailableDates(int $propertyId): array
    {
        $dates = [];

        $blockedDates = BlockedDate::where('property_id', $propertyId)
            ->where('end_date', '>=', now()->toDateString())
            ->get(['start_date', 'end_date']);

        foreach ($blockedDates as $blocked) {
            $start = $blocked->start_date->format('Y-m-d');
            $end = $blocked->end_date->format('Y-m-d');
            while ($start <= $end) {
                $dates[] = $start;
                $start = date('Y-m-d', strtotime($start.' +1 day'));
            }
        }

        $reservations = Reservation::where('property_id', $propertyId)
            ->blockingAvailability()
            ->whereDate('check_out', '>=', now()->toDateString())
            ->get(['check_in', 'check_out']);

        foreach ($reservations as $reservation) {
            $start = $reservation->check_in->format('Y-m-d');
            $end = $reservation->check_out->format('Y-m-d');
            while ($start < $end) {
                $dates[] = $start;
                $start = date('Y-m-d', strtotime($start.' +1 day'));
            }
        }

        return array_unique($dates);
    }
}
