<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\Property;
use App\Models\User;
use App\Rules\Turnstile;
use App\Services\AffiliateCommissionService;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class AffiliatePortalController extends Controller
{
    public function __construct(
        protected AffiliateCommissionService $commissionService
    ) {}

    public function join()
    {
        $user = Auth::user();
        $affiliate = $user?->affiliate;
        $defaultCode = $user ? Affiliate::generateUniqueCode($user->name) : 'PARTNER'.strtoupper(substr(uniqid(), -4));
        $defaultRate = AffiliateCommissionService::getDefaultCommissionRate();

        return view('affiliate.join', compact('defaultCode', 'affiliate', 'user', 'defaultRate'));
    }

    public function storeJoin(Request $request)
    {
        $request->validate([
            'cf-turnstile-response' => [Rule::requiredIf(config('turnstile.enabled')), new Turnstile('affiliate_join')],
        ]);

        $user = Auth::user();

        if (! $user) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'phone' => ['nullable', 'string', 'max:25'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'code' => ['required', 'string', 'min:3', 'max:20', 'alpha_num', 'unique:affiliates,code'],
                'bank_name' => ['nullable', 'string', 'max:100'],
                'account_number' => ['nullable', 'string', 'max:20'],
                'account_name' => ['nullable', 'string', 'max:150'],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'status' => 'active',
            ]);
            $user->assignRole('customer');
            Auth::login($user);
        } else {
            if ($user->affiliate) {
                return redirect()->route('affiliate.dashboard');
            }

            $validated = $request->validate([
                'code' => ['required', 'string', 'min:3', 'max:20', 'alpha_num', 'unique:affiliates,code'],
                'bank_name' => ['nullable', 'string', 'max:100'],
                'account_number' => ['nullable', 'string', 'max:20'],
                'account_name' => ['nullable', 'string', 'max:150'],
            ]);
        }

        $affiliate = Affiliate::create([
            'user_id' => $user->id,
            'code' => strtoupper($validated['code']),
            'commission_rate' => AffiliateCommissionService::getDefaultCommissionRate(),
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
            $user = Auth::user();
            if ($user) {
                $code = Affiliate::generateUniqueCode($user->name);
                $affiliate = Affiliate::create([
                    'user_id' => $user->id,
                    'code' => $code,
                    'commission_rate' => AffiliateCommissionService::getDefaultCommissionRate(),
                    'status' => 'active',
                ]);
            } else {
                return redirect()->route('affiliate.join');
            }
        }

        $properties = Property::active()->select('id', 'title', 'slug', 'price_per_night')->get();
        $recentReferrals = $affiliate->referrals()->with(['reservation.property'])->latest()->take(5)->get();

        $clicksCount = $affiliate->clicks()->count();
        $referralsCount = $affiliate->completed_referrals_count;
        $conversionRate = $clicksCount > 0 ? round(($referralsCount / $clicksCount) * 100, 1) : 0.0;
        $defaultRate = $affiliate->commission_rate ?: AffiliateCommissionService::getDefaultCommissionRate();

        return view('affiliate.dashboard', compact('affiliate', 'properties', 'recentReferrals', 'clicksCount', 'conversionRate', 'defaultRate'));
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
            'amount' => ['required', 'numeric', 'min:'.AffiliateCommissionService::MINIMUM_PAYOUT_AMOUNT],
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

            return back()->with('success', 'Payout request of ₦'.number_format($payout->amount).' submitted successfully. Payment reference: '.$payout->payout_ref);
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

    public function flyer(Request $request)
    {
        $affiliate = $this->getAffiliate();
        if (! $affiliate) {
            return redirect()->route('affiliate.dashboard');
        }

        $layout = $request->query('layout', 'flyer'); // 'flyer' or 'tent'
        $qrService = app(QrCodeService::class);
        $qrDataUri = $qrService->generateReferralQrDataUri($affiliate->referral_url);

        return view('affiliate.flyer', compact('affiliate', 'qrDataUri', 'layout'));
    }

    public function downloadFlyerPdf(Request $request)
    {
        $affiliate = $this->getAffiliate();
        if (! $affiliate) {
            return redirect()->route('affiliate.dashboard');
        }

        $layout = $request->query('layout', 'flyer'); // 'flyer' or 'tent'
        $qrService = app(QrCodeService::class);
        $qrDataUri = $qrService->generateReferralQrDataUri($affiliate->referral_url);

        $pdf = Pdf::loadView('affiliate.flyer-pdf', compact('affiliate', 'qrDataUri', 'layout'));
        $pdf->setPaper('a4', $layout === 'tent' ? 'landscape' : 'portrait');

        $filename = 'booktala-'.$layout.'-'.strtolower($affiliate->code).'.pdf';

        return $pdf->download($filename);
    }

    private function getAffiliate(): ?Affiliate
    {
        return Auth::user()?->affiliate;
    }
}
