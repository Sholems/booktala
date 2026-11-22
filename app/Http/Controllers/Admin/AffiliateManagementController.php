<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliatePayout;
use App\Models\AffiliateReferral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AffiliateManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Affiliate::with('user')->withCount('referrals');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', $search)
                    ->orWhere('bank_name', 'like', $search)
                    ->orWhere('account_name', 'like', $search)
                    ->orWhereHas('user', function ($sub) use ($search) {
                        $sub->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $affiliates = $query->latest()->paginate(20);

        $totalAffiliates = Affiliate::count();
        $totalPaidOut = AffiliatePayout::where('status', 'completed')->sum('amount');
        $totalPendingCommissions = AffiliateReferral::where('status', 'pending')->sum('commission_amount');
        $defaultCommissionRate = \App\Services\AffiliateCommissionService::getDefaultCommissionRate();

        return view('admin.affiliates.index', compact(
            'affiliates',
            'totalAffiliates',
            'totalPaidOut',
            'totalPendingCommissions',
            'defaultCommissionRate'
        ));
    }

    public function updateDefaultRate(Request $request)
    {
        $validated = $request->validate([
            'commission_rate' => ['required', 'numeric', 'min:0.5', 'max:50'],
            'update_existing' => ['nullable', 'boolean'],
        ]);

        $rate = (float) $validated['commission_rate'];
        \App\Models\Setting::set('affiliate_commission_rate', $rate);

        if ($request->boolean('update_existing')) {
            Affiliate::query()->update(['commission_rate' => $rate]);
            return back()->with('success', "Default affiliate commission rate updated to {$rate}% and applied to all existing affiliates.");
        }

        return back()->with('success', "Default affiliate commission rate updated to {$rate}% successfully.");
    }

    public function show(Affiliate $affiliate)
    {
        $affiliate->load(['user', 'payouts' => fn($q) => $q->latest()->take(10)]);
        $referrals = $affiliate->referrals()->with(['reservation.property', 'guest'])->latest()->paginate(15);

        return view('admin.affiliates.show', compact('affiliate', 'referrals'));
    }

    public function update(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'commission_rate' => ['required', 'numeric', 'min:1', 'max:50'],
            'status' => ['required', 'in:active,pending,suspended'],
        ]);

        $affiliate->update($validated);

        return back()->with('success', 'Affiliate account settings updated successfully.');
    }

    public function referrals(Request $request)
    {
        $query = AffiliateReferral::with(['affiliate.user', 'reservation.property', 'guest']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $referrals = $query->latest()->paginate(20);

        return view('admin.affiliates.referrals', compact('referrals'));
    }

    public function payouts(Request $request)
    {
        $query = AffiliatePayout::with('affiliate.user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->latest()->paginate(20);

        return view('admin.affiliates.payouts', compact('payouts'));
    }

    public function completePayout(Request $request, AffiliatePayout $payout)
    {
        $validated = $request->validate([
            'transfer_reference' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $payout->update([
            'status' => 'completed',
            'transfer_reference' => $validated['transfer_reference'],
            'notes' => $validated['notes'] ?? null,
            'processed_by' => Auth::id(),
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Payout marked as completed and settled.');
    }

    public function rejectPayout(Request $request, AffiliatePayout $payout)
    {
        if ($payout->status === 'completed') {
            return back()->with('error', 'Completed payouts cannot be rejected.');
        }

        DB::transaction(function () use ($payout, $request) {
            // Refund balance back to affiliate
            $payout->affiliate->increment('current_balance', $payout->amount);

            $payout->update([
                'status' => 'failed',
                'notes' => $request->input('notes', 'Payout request declined by administrator.'),
                'processed_by' => Auth::id(),
            ]);
        });

        return back()->with('success', 'Payout request rejected and balance refunded to partner.');
    }
}
