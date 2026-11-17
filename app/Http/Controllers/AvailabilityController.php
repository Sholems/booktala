<?php

namespace App\Http\Controllers;

use App\Models\BlockedDate;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(private PricingService $pricingService) {}

    public function check(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'property_id' => 'nullable|exists:properties,id',
        ]);

        $checkIn = Carbon::parse($request->check_in)->startOfDay();
        $checkOut = Carbon::parse($request->check_out)->startOfDay();
        $nights = (int) $checkIn->diffInDays($checkOut);

        if ($request->property_id) {
            $property = Property::with('images')->active()->findOrFail($request->property_id);
            return response()->json($this->checkPropertyAvailability($property, $checkIn, $checkOut, $nights));
        }

        $properties = Property::with('images')
            ->active()
            ->get()
            ->map(fn ($property) => $this->checkPropertyAvailability($property, $checkIn, $checkOut, $nights))
            ->sortByDesc('available')
            ->values();

        return response()->json([
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'nights' => $nights,
            'properties' => $properties,
        ]);
    }

    private function checkPropertyAvailability(Property $property, Carbon $checkIn, Carbon $checkOut, int $nights): array
    {
        $available = $this->pricingService->isAvailable($property, $checkIn, $checkOut);

        return [
            'id' => $property->id,
            'name' => $property->title,
            'slug' => $property->slug,
            'available' => $available,
            'available_rooms' => $available ? 1 : 0,
            'total_rooms' => 1,
            'price_per_night' => $property->price_per_night,
            'total_price' => $property->price_per_night * $nights,
            'nights' => $nights,
            'images' => $property->images->take(3)->map(fn ($img) => $img->image_path)->values(),
        ];
    }

    public function getCalendar($propertyId)
    {
        $property = Property::active()->findOrFail($propertyId);
        $startDate = now()->startOfMonth()->startOfDay();
        $endDate = now()->addMonths(6)->endOfMonth()->startOfDay();

        $reservations = Reservation::where('property_id', $property->id)
            ->whereIn('booking_status', ['pending', 'confirmed', 'checked_in'])
            ->whereDate('check_out', '>', $startDate)
            ->whereDate('check_in', '<=', $endDate)
            ->get(['check_in', 'check_out', 'booking_status']);

        $blockedDates = BlockedDate::where('property_id', $property->id)
            ->whereDate('end_date', '>=', $startDate)
            ->whereDate('start_date', '<=', $endDate)
            ->get(['start_date', 'end_date', 'reason']);

        $calendar = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateStr = $date->toDateString();

            // The check-out day itself is free for the next guest.
            $reserved = $reservations->first(fn ($res) => $dateStr >= $res->check_in->toDateString()
                && $dateStr < $res->check_out->toDateString());

            $blocked = $blockedDates->first(fn ($block) => $dateStr >= Carbon::parse($block->start_date)->toDateString()
                && $dateStr <= Carbon::parse($block->end_date)->toDateString());

            $calendar[] = [
                'date' => $dateStr,
                'available' => !$reserved && !$blocked,
                'status' => $reserved ? $reserved->booking_status : ($blocked ? 'blocked' : 'available'),
            ];
        }

        return response()->json([
            'property' => $property->title,
            'calendar' => $calendar,
        ]);
    }
}
