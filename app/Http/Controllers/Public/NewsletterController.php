<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\NewsletterSubscribeRequest;
use App\Models\NewsletterSubscriber;

class NewsletterController extends Controller
{
    public function subscribe(NewsletterSubscribeRequest $request)
    {
        $validated = $request->validated();

        NewsletterSubscriber::updateOrCreate(
            ['email' => strtolower(trim($validated['email']))],
            [
                'name' => $validated['name'] ?? null,
                'status' => 'subscribed',
                'source' => $request->input('source', 'homepage_vip_banner'),
                'ip_address' => $request->ip(),
                'subscribed_at' => now(),
            ]
        );

        $successMsg = 'Welcome to the BookTala VIP Club! Use promo code WELCOME10 for 10% off your first stay.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'promo_code' => 'WELCOME10',
                'message' => $successMsg,
            ]);
        }

        return redirect()->back()->with('newsletter_success', $successMsg);
    }
}
