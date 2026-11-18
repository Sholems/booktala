<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\GuestProfile;
use App\Models\LoyaltyTransaction;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoyaltyController extends Controller
{
    public function index()
    {
        $profile = GuestProfile::firstOrCreate(
            ['user_id' => auth()->id()],
            ['loyalty_points' => 0, 'loyalty_tier' => 'bronze', 'total_stays' => 0]
        );

        $transactions = LoyaltyTransaction::where('guest_profile_id', $profile->id)
            ->latest()
            ->limit(20)
            ->get();

        $tiers = GuestProfile::tiers();
        $currentTier = $tiers[$profile->loyalty_tier];
        $nextTier = null;

        $tierKeys = array_keys($tiers);
        $currentIndex = array_search($profile->loyalty_tier, $tierKeys);
        if ($currentIndex < count($tierKeys) - 1) {
            $nextTierKey = $tierKeys[$currentIndex + 1];
            $nextTier = $tiers[$nextTierKey];
            $nextTier['key'] = $nextTierKey;
            $nextTier['points_needed'] = $nextTier['min_points'] - $profile->loyalty_points;
        }

        return view('customer.loyalty.index', compact('profile', 'transactions', 'tiers', 'currentTier', 'nextTier'));
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'points' => 'required|integer|min:100',
        ]);

        $profile = GuestProfile::where('user_id', auth()->id())->firstOrFail();

        if ($profile->loyalty_points < $request->points) {
            return back()->with('error', 'Insufficient points');
        }

        $discount = $request->points / 100;

        $profile->decrement('loyalty_points', $request->points);

        LoyaltyTransaction::create([
            'guest_profile_id' => $profile->id,
            'type' => 'redeem',
            'points' => -$request->points,
            'description' => 'Redeemed for ₦'.number_format($discount, 2).' discount',
        ]);

        return back()->with('success', 'Points redeemed! You got ₦'.number_format($discount, 2).' discount voucher');
    }

    public function applyDiscount(Request $request, $reservationId)
    {
        $request->validate([
            'points' => 'required|integer|min:100',
        ]);

        $profile = GuestProfile::where('user_id', auth()->id())->firstOrFail();

        $reservation = Reservation::where('id', $reservationId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($reservation->payment_status !== 'pending' || $reservation->booking_status === 'cancelled') {
            return back()->with('error', 'Points can only be applied to unpaid reservations.');
        }

        if ($profile->loyalty_points < $request->points) {
            return back()->with('error', 'Insufficient points');
        }

        $discount = $request->points / 100;

        $discountableAmount = max(
            0,
            (float) $reservation->total_amount
                - (float) $reservation->processing_fee
                - (float) $reservation->caution_fee,
        );

        if ($discount > $discountableAmount) {
            return back()->with('error', 'Discount cannot be applied to the processing or caution fees.');
        }

        DB::transaction(function () use ($profile, $reservation, $request, $discount) {
            $profile->decrement('loyalty_points', $request->points);

            LoyaltyTransaction::create([
                'guest_profile_id' => $profile->id,
                'type' => 'redeem',
                'points' => -$request->points,
                'description' => 'Applied to reservation',
                'reservation_id' => $reservation->id,
            ]);

            $reservation->update([
                'discount_amount' => ($reservation->discount_amount ?? 0) + $discount,
                'total_amount' => $reservation->total_amount - $discount,
            ]);
        });

        return back()->with('success', 'Discount applied!');
    }
}
