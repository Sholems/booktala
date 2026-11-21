<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\Property;
use App\Services\AffiliateCommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class AffiliatePortalController extends Controller
{
    public function __construct(
        protected AffiliateCommissionService $commissionService
    ) {}

    public function join()
    {
        if (! Auth::check()) {
            return redirect()->guest(route('login'))->with('info', 'Please sign in or create an account to join the BookTala Affiliate Program.');
        }

        $affiliate = Auth::user()->affiliate;
        if ($affiliate) {
            return redirect()->route('affiliate.dashboard');
        }

        $defaultCode = Affiliate::generateUniqueCode(Auth::user()->name);

        return view('affiliate.join', compact('defaultCode'));
    }

    public function storeJoin(Request $request)
    {
        $user = Auth::user();
        if ($user->affiliate) {
            return redirect()->route('affiliate.dashboard');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:20', 'alpha_num', 'unique:affiliates,code'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:20'],
            'account_name' => ['nullable', 'string', 'max:150'],
        ]);

        $affiliate = Affiliate::create([
            'user_id' => $user->id,
            'code' => strtoupper($validated['code']),
            'commission_rate' => AffiliateCommissionService::DEFAULT_COMMISSION_PERCENT,
            'bank_name' => $validated['bank_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'account_name' => $validated['account_name'] ?? null,
            'status' => 'active',
        ]);

        return redirect()->route('affiliate.dashboard')->with('success', 'Welcome to the BookTala Affiliate Program! Your referral link is now active.');
    }

    public function dashboard()
    {
        $affiliate = $this->getAffiliate();
        if (! $affiliate) {
            return redirect()->route('affiliate.join');
        }

        $properties = Property::active()->select('id', 'title', 'slug', 'price_per_night')->get();
        $recentReferrals = $affiliate->referrals()->with(['reservation.property'])->latest()->take(5)->get();

        $clicksCount = $affiliate->clicks()->count();
        $referralsCount = $affiliate->completed_referrals_count;
        $conversionRate = $clicksCount > 0 ? round(($referralsCount / $clicksCount) * 100, 1) : 0.0;

        return view('affiliate.dashboard', compact('affiliate', 'properties', 'recentReferrals', 'clicksCount', 'conversionRate'));
    }

    public function referrals()
    {
        $affiliate = $this->getAffiliate();
        if (! $affiliate) {
            return redirect()->route('affiliate.join');
        }

        $referrals = $affiliate->referrals()
            ->with(['reservation.property', 'guest'])
            ->latest()
            ->paginate(15);

        return view('affiliate.referrals', compact('affiliate', 'referrals'));
    }

    public function payouts()
    {
        $affiliate = $this->getAffiliate();
        if (! $affiliate) {
            return redirect()->route('affiliate.join');
        }

        $payouts = $affiliate->payouts()->latest()->paginate(15);
        $minimumPayout = AffiliateCommissionService::MINIMUM_PAYOUT_AMOUNT;

        return view('affiliate.payouts', compact('affiliate', 'payouts', 'minimumPayout'));
    }

    public function updateBank(Request $request)
    {
        $affiliate = $this->getAffiliate();
        if (! $affiliate) {
            return redirect()->route('affiliate.join');
        }

        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'min:10', 'max:10'],
            'account_name' => ['required', 'string', 'max:150'],
        ]);

        $affiliate->update($validated);

        return back()->with('success', 'Bank payout details updated successfully.');
    }

    public function requestPayout(Request $request)
    {
        $affiliate = $this->getAffiliate();
        if (! $affiliate) {
            return redirect()->route('affiliate.join');
        }

        if (empty($affiliate->account_number) || empty($affiliate->bank_name)) {
            return back()->with('error', 'Please update your Nigerian bank payout details before requesting a withdrawal.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:' . AffiliateCommissionService::MINIMUM_PAYOUT_AMOUNT],
        ]);

        try {
            $payout = $this->commissionService->requestPayout(
                $affiliate,
                (float) $validated['amount'],
                [
                    'bank_name' => $affiliate->bank_name,
                    'account_number' => $affiliate->account_number,
                    'account_name' => $affiliate->account_name,
                ]
            );

            return back()->with('success', 'Payout request of ₦' . number_format($payout->amount) . ' submitted successfully. Payment reference: ' . $payout->payout_ref);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function marketing()
    {
        $affiliate = $this->getAffiliate();
        if (! $affiliate) {
            return redirect()->route('affiliate.join');
        }

        $properties = Property::active()->with('primaryImage')->take(6)->get();

        return view('affiliate.marketing', compact('affiliate', 'properties'));
    }

    private function getAffiliate(): ?Affiliate
    {
        return Auth::user()?->affiliate;
    }
}
