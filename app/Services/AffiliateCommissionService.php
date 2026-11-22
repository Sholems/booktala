<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateClick;
use App\Models\AffiliatePayout;
use App\Models\AffiliateReferral;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AffiliateCommissionService
{
    public const MINIMUM_PAYOUT_AMOUNT = 20000.00;
    public const DEFAULT_COMMISSION_PERCENT = 10.00;

    /**
     * Resolve the active default affiliate commission rate configured by the admin.
     */
    public static function getDefaultCommissionRate(): float
    {
        return (float) \App\Models\Setting::get('affiliate_commission_rate', self::DEFAULT_COMMISSION_PERCENT);
    }

    /**
     * Attaches an affiliate referral to a confirmed reservation if eligible.
     */
    public function recordReferral(Reservation $reservation, ?string $codeOverride = null): ?AffiliateReferral
    {
        // 1. Resolve referral code
        $code = $codeOverride 
            ?: request()?->cookie('bt_affiliate') 
            ?: request()?->input('affiliate_code')
            ?: request()?->input('ref');

        if (! $code) {
            return null;
        }

        $code = strtoupper(trim(preg_replace('/[^A-Za-z0-9]/', '', (string) $code)));
        $affiliate = Affiliate::where('code', $code)->where('status', 'active')->first();
        if (! $affiliate) {
            return null;
        }

        // 2. Anti-fraud: prevent self-referrals
        if ($affiliate->user_id === $reservation->user_id) {
            return null;
        }

        $affiliateUser = $affiliate->user;
        $guestUser = $reservation->user;
        if ($affiliateUser && $guestUser) {
            if (strtolower((string) $affiliateUser->email) === strtolower((string) $guestUser->email)) {
                return null;
            }
            if (! empty($affiliateUser->phone) && ! empty($guestUser->phone) && $affiliateUser->phone === $guestUser->phone) {
                return null;
            }
        }

        // 3. Avoid duplicate attribution
        $existing = AffiliateReferral::where('reservation_id', $reservation->id)->first();
        if ($existing) {
            return $existing;
        }

        // 4. Calculate Net Accommodation Base (Strict Rule 1)
        // Deduct caution fee and processing fee so commission is solely on room stay.
        $grossTotal = (float) $reservation->total_amount;
        $cautionFee = (float) ($reservation->caution_fee ?? 0);
        $processingFee = (float) ($reservation->processing_fee ?? 0);
        $netBase = max(0.00, $grossTotal - $cautionFee - $processingFee);

        $rate = (float) ($affiliate->commission_rate ?: self::getDefaultCommissionRate());
        $commissionAmount = round(($netBase * $rate) / 100, 2);

        if ($commissionAmount <= 0) {
            return null;
        }

        // Maturation is set to 24 hours post checkout
        $maturedAt = Carbon::parse($reservation->check_out)->addDay();

        $referral = AffiliateReferral::create([
            'affiliate_id' => $affiliate->id,
            'reservation_id' => $reservation->id,
            'guest_user_id' => $reservation->user_id,
            'base_accommodation_amount' => $netBase,
            'commission_rate' => $rate,
            'commission_amount' => $commissionAmount,
            'status' => 'pending',
            'check_in' => $reservation->check_in,
            'check_out' => $reservation->check_out,
            'matured_at' => $maturedAt,
        ]);

        // Mark click as converted if tracking
        if (request()) {
            AffiliateClick::where('affiliate_id', $affiliate->id)
                ->where('ip_address', request()->ip())
                ->whereNull('converted_at')
                ->latest()
                ->first()
                ?->update(['converted_at' => now()]);
        }

        return $referral;
    }

    /**
     * Matures pending commissions 24 hours post-checkout if reservation was fulfilled.
     */
    public function matureCommissions(): int
    {
        $pending = AffiliateReferral::where('status', 'pending')
            ->where('matured_at', '<=', now())
            ->with(['reservation', 'affiliate'])
            ->get();

        $maturedCount = 0;

        foreach ($pending as $ref) {
            $reservation = $ref->reservation;

            // If reservation was cancelled or refunded, void the referral
            if (! $reservation || in_array($reservation->status, ['cancelled', 'rejected'], true)) {
                $ref->update([
                    'status' => 'void',
                    'void_reason' => 'Reservation cancelled before stay completed',
                ]);
                continue;
            }

            DB::transaction(function () use ($ref) {
                $ref->update(['status' => 'approved']);
                $affiliate = $ref->affiliate;
                $affiliate->increment('current_balance', $ref->commission_amount);
                $affiliate->increment('lifetime_earnings', $ref->commission_amount);
            });

            $maturedCount++;
        }

        return $maturedCount;
    }

    /**
     * Voids referral if a reservation is refunded or cancelled.
     */
    public function voidReferral(Reservation $reservation, string $reason = 'Reservation cancelled'): bool
    {
        $ref = AffiliateReferral::where('reservation_id', $reservation->id)->first();
        if (! $ref || $ref->status === 'void') {
            return false;
        }

        DB::transaction(function () use ($ref, $reason) {
            if ($ref->status === 'approved') {
                $affiliate = $ref->affiliate;
                $newBalance = max(0.00, (float) $affiliate->current_balance - (float) $ref->commission_amount);
                $affiliate->update(['current_balance' => $newBalance]);
            }

            $ref->update([
                'status' => 'void',
                'void_reason' => $reason,
            ]);
        });

        return true;
    }

    /**
     * Submits a payout withdrawal request.
     */
    public function requestPayout(Affiliate $affiliate, float $amount, array $bankDetails): AffiliatePayout
    {
        if ($amount < self::MINIMUM_PAYOUT_AMOUNT) {
            throw new InvalidArgumentException('Minimum payout amount is ₦' . number_format(self::MINIMUM_PAYOUT_AMOUNT, 2));
        }

        if ((float) $affiliate->current_balance < $amount) {
            throw new InvalidArgumentException('Requested payout amount exceeds available balance.');
        }

        return DB::transaction(function () use ($affiliate, $amount, $bankDetails) {
            $affiliate->decrement('current_balance', $amount);

            return AffiliatePayout::create([
                'affiliate_id' => $affiliate->id,
                'payout_ref' => AffiliatePayout::generatePayoutRef(),
                'amount' => $amount,
                'bank_name' => $bankDetails['bank_name'] ?? $affiliate->bank_name,
                'account_number' => $bankDetails['account_number'] ?? $affiliate->account_number,
                'account_name' => $bankDetails['account_name'] ?? $affiliate->account_name,
                'status' => 'requested',
            ]);
        });
    }
}
