<?php

namespace App\Services;

use App\Models\BlockedDate;
use App\Models\DiscountCode;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\Unit;
use Carbon\Carbon;

class PricingService
{
    /**
     * Calculate stay quote taking into account dynamic pricing rules, seasonal rates,
     * weekend adjustments, and length-of-stay discounts.
     */
    public function calculatePrice(Unit $property, $checkIn, $checkOut, $guests = 1, $discountCode = null): array
    {
        $checkIn = Carbon::parse($checkIn);
        $checkOut = Carbon::parse($checkOut);
        $nights = $checkIn->diffInDays($checkOut);

        if ($nights < ($property->min_nights ?: 1)) {
            throw new \DomainException("Minimum stay is {$property->min_nights} nights");
        }

        if ($property->max_nights && $nights > $property->max_nights) {
            throw new \DomainException("Maximum stay is {$property->max_nights} nights");
        }

        if ($guests > $property->max_guests) {
            throw new \DomainException("Maximum {$property->max_guests} guests allowed");
        }

        // Check active seasonal / event minimum stay rules
        $this->enforceRuleMinNights($property, $checkIn, $checkOut, $nights);

        $this->checkAvailability($property, $checkIn, $checkOut);

        $subtotal = 0;
        $nightlyBreakdown = [];
        $appliedRules = [];
        $currentDate = $checkIn->copy();

        while ($currentDate->lt($checkOut)) {
            $nightData = $this->getNightlyRateDetails($property, $currentDate, $nights);
            $subtotal += $nightData['price'];
            $nightlyBreakdown[] = $nightData;

            if (! empty($nightData['rule_name']) && ! in_array($nightData['rule_name'], $appliedRules)) {
                $appliedRules[] = $nightData['rule_name'];
            }

            $currentDate->addDay();
        }

        // Length-of-stay weekly / monthly discounts if defined on unit and not already overridden
        $lengthOfStayDiscount = 0;
        if ($nights >= 30 && ! empty($property->price_per_month)) {
            $standardSubtotal = $property->price_per_night * $nights;
            if ($standardSubtotal > $property->price_per_month) {
                $lengthOfStayDiscount = max(0, $subtotal - (float) $property->price_per_month);
            }
        } elseif ($nights >= 7 && ! empty($property->price_per_week)) {
            $standardSubtotal = $property->price_per_night * $nights;
            if ($standardSubtotal > $property->price_per_week) {
                $lengthOfStayDiscount = max(0, $subtotal - (float) $property->price_per_week);
            }
        }

        $effectiveSubtotal = max(0, $subtotal - $lengthOfStayDiscount);

        $discountAmount = 0;
        $appliedDiscountCode = null;
        if ($discountCode) {
            $discount = DiscountCode::where('code', $discountCode)->first();
            if ($discount && $discount->isValid()) {
                $discountAmount = $discount->calculateDiscount($effectiveSubtotal, $nights);
                $appliedDiscountCode = $discount->code;
            }
        }

        $taxRate = (float) Setting::get('vat_rate', 7.5);
        $taxAmount = ($effectiveSubtotal - $discountAmount) * ($taxRate / 100);
        $processingFee = (float) config('booking.processing_fee', 2000);
        $cautionFee = (float) config('booking.caution_fee', 20000);
        $totalAmount = $effectiveSubtotal - $discountAmount + $taxAmount + $processingFee + $cautionFee;

        return [
            'nights' => $nights,
            'guests' => $guests,
            'subtotal' => round($effectiveSubtotal, 2),
            'gross_subtotal' => round($subtotal, 2),
            'length_of_stay_discount' => round($lengthOfStayDiscount, 2),
            'discount_amount' => round($discountAmount, 2),
            'discount_code' => $appliedDiscountCode,
            'tax_amount' => round($taxAmount, 2),
            'processing_fee' => round($processingFee, 2),
            'caution_fee' => round($cautionFee, 2),
            'total_amount' => round($totalAmount, 2),
            'price_per_night' => $property->price_per_night,
            'nightly_breakdown' => $nightlyBreakdown,
            'applied_rules' => $appliedRules,
        ];
    }

    /**
     * Compute exact nightly rate and applied dynamic pricing rule for a specific date.
     */
    public function getNightlyRateDetails(Unit $property, Carbon $date, int $stayNights = 1): array
    {
        $basePrice = (float) $property->price_per_night;
        $weekendPrice = (float) ($property->weekend_price ?: $basePrice);
        $isWeekend = $this->isWeekend($date);
        $standardRate = $isWeekend ? $weekendPrice : $basePrice;

        // Find matching active rules ordered by priority & specificity
        $activeRule = $this->findMatchingRule($property, $date, $stayNights);

        if (! $activeRule) {
            return [
                'date' => $date->toDateString(),
                'day_name' => $date->format('D'),
                'price' => $standardRate,
                'is_weekend' => $isWeekend,
                'rule_name' => $isWeekend && $property->weekend_price ? 'Weekend Rate' : null,
            ];
        }

        $calculatedPrice = $standardRate;

        if ($activeRule->price_override !== null && $activeRule->price_override > 0) {
            $calculatedPrice = (float) $activeRule->price_override;
        } elseif ($activeRule->adjustment_type === PricingRule::ADJUSTMENT_PERCENTAGE_MARKUP && $activeRule->adjustment_value) {
            $calculatedPrice = $standardRate * (1 + ((float) $activeRule->adjustment_value / 100));
        } elseif ($activeRule->adjustment_type === PricingRule::ADJUSTMENT_PERCENTAGE_DISCOUNT && $activeRule->adjustment_value) {
            $calculatedPrice = $standardRate * (1 - ((float) $activeRule->adjustment_value / 100));
        } elseif ($activeRule->adjustment_type === PricingRule::ADJUSTMENT_FIXED_MARKUP && $activeRule->adjustment_value) {
            $calculatedPrice = $standardRate + (float) $activeRule->adjustment_value;
        } elseif ($activeRule->discount_percentage) {
            $calculatedPrice = $standardRate * (1 - ((float) $activeRule->discount_percentage / 100));
        }

        return [
            'date' => $date->toDateString(),
            'day_name' => $date->format('D'),
            'price' => round(max(0, $calculatedPrice), 2),
            'is_weekend' => $isWeekend,
            'rule_name' => $activeRule->name,
            'rule_type' => $activeRule->type,
        ];
    }

    /**
     * Find best matching active rule for a unit and date.
     */
    protected function findMatchingRule(Unit $property, Carbon $date, int $stayNights = 1): ?PricingRule
    {
        $rules = PricingRule::query()
            ->where('is_active', true)
            ->where(function ($q) use ($property) {
                $q->where('property_id', $property->id)
                    ->orWhere(function ($sq) use ($property) {
                        $sq->whereNull('property_id')
                            ->where('site_id', $property->site_id);
                    })
                    ->orWhere(function ($gq) {
                        $gq->whereNull('property_id')->whereNull('site_id');
                    });
            })
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderByRaw('CASE WHEN property_id IS NOT NULL THEN 1 WHEN site_id IS NOT NULL THEN 2 ELSE 3 END')
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get();

        $dayOfWeek = $date->dayOfWeekIso; // 1 (Monday) to 7 (Sunday)

        foreach ($rules as $rule) {
            // Check day of week filter if configured
            if (! empty($rule->days_of_week) && is_array($rule->days_of_week)) {
                if (! in_array($dayOfWeek, $rule->days_of_week)) {
                    continue;
                }
            }

            return $rule;
        }

        return null;
    }

    /**
     * Check if any active seasonal/event rule enforces higher minimum nights.
     */
    protected function enforceRuleMinNights(Unit $property, Carbon $checkIn, Carbon $checkOut, int $nights): void
    {
        $rules = PricingRule::query()
            ->where('is_active', true)
            ->whereNotNull('min_nights')
            ->where('min_nights', '>', 0)
            ->where(function ($q) use ($property) {
                $q->where('property_id', $property->id)
                    ->orWhere('site_id', $property->site_id)
                    ->orWhere(function ($gq) {
                        $gq->whereNull('property_id')->whereNull('site_id');
                    });
            })
            ->whereDate('start_date', '<', $checkOut)
            ->whereDate('end_date', '>=', $checkIn)
            ->get();

        foreach ($rules as $rule) {
            if ($nights < $rule->min_nights) {
                throw new \DomainException("Minimum stay for dates covered by '{$rule->name}' is {$rule->min_nights} nights.");
            }
        }
    }

    /**
     * Generate calendar array of daily prices for simulator / calendar view.
     */
    public function getDailyPriceCalendar(Unit $property, Carbon $start, Carbon $end): array
    {
        $calendar = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $calendar[] = $this->getNightlyRateDetails($property, $current);
            $current->addDay();
        }

        return $calendar;
    }

    public function isWeekend(Carbon $date): bool
    {
        return $date->isFriday() || $date->isSaturday() || $date->isSunday();
    }

    public function checkAvailability(Unit $property, $checkIn, $checkOut): bool
    {
        $checkIn = Carbon::parse($checkIn)->toDateString();
        $checkOut = Carbon::parse($checkOut)->toDateString();
        $lastNight = Carbon::parse($checkOut)->subDay()->toDateString();

        $blockedDates = BlockedDate::where('property_id', $property->id)
            ->whereDate('start_date', '<=', $lastNight)
            ->whereDate('end_date', '>=', $checkIn)
            ->exists();

        if ($blockedDates) {
            throw new \DomainException('Selected dates are not available');
        }

        $reservations = Reservation::where('property_id', $property->id)
            ->blockingAvailability()
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn)
            ->exists();

        if ($reservations) {
            throw new \DomainException('Selected dates are already booked');
        }

        return true;
    }

    public function isAvailable(Unit $property, $checkIn, $checkOut): bool
    {
        try {
            return $this->checkAvailability($property, $checkIn, $checkOut);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getBlockedDates(Unit $property, $year = null, $month = null)
    {
        $query = BlockedDate::where('property_id', $property->id);

        if ($year && $month) {
            $query->whereYear('start_date', $year)
                ->whereMonth('start_date', $month);
        }

        return $query->get();
    }
}
