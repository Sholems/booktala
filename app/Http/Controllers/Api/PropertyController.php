<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiBookPropertyRequest;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\BookingService;
use App\Services\PricingService;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::with('images', 'amenities')->active();

        if ($request->location) {
            $location = '%'.$request->location.'%';
            $query->where(function ($q) use ($location) {
                $q->where('city', 'like', $location)
                    ->orWhere('state', 'like', $location)
                    ->orWhere('address', 'like', $location);
            });
        }

        if ($request->min_price) {
            $query->where('price_per_night', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('price_per_night', '<=', $request->max_price);
        }

        if ($request->bedrooms) {
            $query->where('bedrooms', '>=', $request->bedrooms);
        }

        if ($request->guests) {
            $query->where('max_guests', '>=', $request->guests);
        }

        $properties = $query->paginate(20);

        return response()->json($properties);
    }

    public function show($id)
    {
        $property = Property::with('images', 'amenities', 'reviews')->active()->findOrFail($id);

        return response()->json([
            'property' => $property,
            'average_rating' => $property->reviews()->avg('overall_rating'),
            'review_count' => $property->reviews()->count(),
        ]);
    }

    public function availability(Request $request, $id, PricingService $pricingService)
    {
        $property = Property::active()->findOrFail($id);

        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'nullable|integer|min:1',
            'discount_code' => 'nullable|string',
        ]);

        try {
            $pricing = $pricingService->calculatePrice(
                $property,
                $request->check_in,
                $request->check_out,
                (int) ($request->guests ?? 1)
            );
        } catch (\Exception $e) {
            return response()->json(['available' => false, 'message' => $e->getMessage()]);
        }

        return response()->json(array_merge(['available' => true], $pricing));
    }

    public function book(ApiBookPropertyRequest $request, $id, BookingService $bookingService)
    {
        $property = Property::active()->findOrFail($id);

        $data = $request->validated();

        // Price is always computed server-side; any client-sent total is ignored.
        try {
            [$reservation, $authorizationUrl] = $bookingService->startBooking($request->user(), $property, $data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'reservation' => $reservation,
            'authorization_url' => $authorizationUrl,
            'message' => 'Reservation created. Proceed to payment.',
        ], 201);
    }

    public function myReservations(Request $request)
    {
        $reservations = Reservation::where('user_id', $request->user()->id)
            ->with('property.images')
            ->latest()
            ->paginate(20);

        return response()->json($reservations);
    }
}
