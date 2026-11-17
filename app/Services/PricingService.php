<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Reservation;
use App\Models\DiscountCode;
use App\Models\PricingRule;
use App\Models\BlockedDate;
use Carbon\Carbon;
use App\Models\Setting;

class PricingService
{
    public function calculatePrice(Property $property, $checkIn, $checkOut, $guests = 1, $discountCode = null)
    {
        $checkIn = Carbon::parse($checkIn);
        $checkOut = Carbon::parse($checkOut);
        $nights = $checkIn->diffInDays($checkOut);

        if ($nights < $property->min_nights) {
            throw new \Exception("Minimum stay is {$property->min_nights} nights");
        }

        if ($nights > $property->max_nights) {
            throw new \Exception("Maximum stay is {$property->max_nights} nights");
        }

        if ($guests > $property->max_guests) {
            throw new \Exception("Maximum {$property->max_guests} guests allowed");
        }

        $this->checkAvailability($property, $checkIn, $checkOut);

        $subtotal = 0;
        $currentDate = $checkIn->copy();

        while ($currentDate->lt($checkOut)) {
            $price = $this->getNightlyPrice($property, $currentDate);
            $subtotal += $price;
            $currentDate->addDay();
        }

        $discountAmount = 0;
        if ($discountCode) {
            $discountCode = DiscountCode::where('code', $discountCode)->first();
            if ($discountCode && $discountCode->isValid()) {
                $discountAmount = $discountCode->calculateDiscount($subtotal, $nights);
                $discountCode->increment('used_count');
            }
        }

        $taxRate = (float) Setting::get('vat_rate', 7.5);
        $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        return [
            'nights' => $nights,
            'guests' => $guests,
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'price_per_night' => $property->price_per_night,
        ];
    }

    private function getNightlyPrice(Property $property, Carbon $date)
    {
        $pricingRule = PricingRule::where('property_id', $property->id)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($pricingRule) {
            if ($pricingRule->price_override) {
                return $pricingRule->price_override;
            }
            if ($pricingRule->discount_percentage) {
                $price = $this->isWeekend($date) && $property->weekend_price
                    ? $property->weekend_price
                    : $property->price_per_night;
                return $price * (1 - $pricingRule->discount_percentage / 100);
            }
        }

        if ($this->isWeekend($date) && $property->weekend_price) {
            return $property->weekend_price;
        }

        return $property->price_per_night;
    }

    private function isWeekend(Carbon $date): bool
    {
        return $date->isSaturday() || $date->isSunday();
    }

    public function checkAvailability(Property $property, $checkIn, $checkOut)
    {
        $checkIn = Carbon::parse($checkIn)->toDateString();
        $checkOut = Carbon::parse($checkOut)->toDateString();
        $lastNight = Carbon::parse($checkOut)->subDay()->toDateString();

        // Blocked ranges are inclusive of both start_date and end_date.
        $blockedDates = BlockedDate::where('property_id', $property->id)
            ->whereDate('start_date', '<=', $lastNight)
            ->whereDate('end_date', '>=', $checkIn)
            ->exists();

        if ($blockedDates) {
            throw new \Exception('Selected dates are not available');
        }

        // Stays overlap when one starts before the other ends; a checkout day
        // can be the next guest's check-in day.
        $reservations = Reservation::where('property_id', $property->id)
            ->whereIn('booking_status', ['pending', 'confirmed', 'checked_in'])
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn)
            ->exists();

        if ($reservations) {
            throw new \Exception('Selected dates are already booked');
        }

        return true;
    }

    public function isAvailable(Property $property, $checkIn, $checkOut): bool
    {
        try {
            return $this->checkAvailability($property, $checkIn, $checkOut);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getBlockedDates(Property $property, $year = null, $month = null)
    {
        $query = BlockedDate::where('property_id', $property->id);

        if ($year && $month) {
            $query->whereYear('start_date', $year)
                ->whereMonth('start_date', $month);
        }

        return $query->get();
    }
}