<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvailabilityCheckRequest;
use App\Models\BlockedDate;
use App\Models\Reservation;
use App\Models\Unit;
use App\Services\PricingService;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
    public function __construct(private PricingService $pricingService) {}

    public function check(AvailabilityCheckRequest $request)
    {
        $request->validated();

        $checkIn = Carbon::parse($request->check_in)->startOfDay();
        $checkOut = Carbon::parse($request->check_out)->startOfDay();
        $nights = (int) $checkIn->diffInDays($checkOut);
        $guests = (int) $request->input('guests', 1);
        $discountCode = $request->input('discount_code');

        if ($request->property_id) {
            $property = Unit::with('images')->active()->findOrFail($request->property_id);

            return response()->json($this->checkPropertyAvailability($property, $checkIn, $checkOut, $nights, $guests, $discountCode));
        }

        $properties = Unit::with('images')
            ->active()
            ->get()
            ->map(fn ($property) => $this->checkPropertyAvailability($property, $checkIn, $checkOut, $nights, $guests, $discountCode))
            ->sortByDesc('available')
            ->values();

        return response()->json([
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'nights' => $nights,
            'properties' => $properties,
        ]);
    }

    private function checkPropertyAvailability(Unit $property, Carbon $checkIn, Carbon $checkOut, int $nights, int $guests = 1, ?string $discountCode = null): array
    {
        $pricing = null;
        $errorMessage = null;

        try {
            $pricing = $this->pricingService->calculatePrice($property, $checkIn, $checkOut, $guests, $discountCode);
            $available = true;
        } catch (\Exception $e) {
            $available = false;
            $errorMessage = $e->getMessage();
        }

        $total = $pricing['total_amount'] ?? null;
        $partial = $total !== null ? round($total * 0.5, 2) : null;

        return [
            'id' => $property->id,
            'name' => $property->title,
            'slug' => $property->slug,
            'available' => $available,
            'message' => $errorMessage,
            'available_rooms' => $available ? 1 : 0,
            'total_rooms' => 1,
            'price_per_night' => $property->price_per_night,
            'subtotal' => $pricing['subtotal'] ?? (float) $property->price_per_night * $nights,
            'discount_amount' => $pricing['discount_amount'] ?? 0,
            'discount_code' => $pricing['discount_code'] ?? null,
            'discount_applied' => ! empty($pricing['discount_code']) && ($pricing['discount_amount'] ?? 0) > 0,
            'tax_amount' => $pricing['tax_amount'] ?? null,
            'processing_fee' => $pricing['processing_fee'] ?? (float) config('booking.processing_fee'),
            'caution_fee' => $pricing['caution_fee'] ?? (float) config('booking.caution_fee'),
            'total_price' => $total,
            'partial_price' => $partial,
            'nights' => $nights,
            'guests' => $guests,
            'images' => $property->images->take(3)->map(fn ($img) => $img->image_path)->values(),
        ];
    }

    public function getCalendar($propertyId)
    {
        $property = Unit::active()->findOrFail($propertyId);
        $startDate = now()->startOfMonth()->startOfDay();
        $endDate = now()->addMonths(6)->endOfMonth()->startOfDay();

        $reservations = Reservation::where('property_id', $property->id)
            ->blockingAvailability()
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
                'available' => ! $reserved && ! $blocked,
                'status' => $reserved ? $reserved->booking_status : ($blocked ? 'blocked' : 'available'),
            ];
        }

        return response()->json([
            'property' => $property->title,
            'calendar' => $calendar,
        ]);
    }
}
