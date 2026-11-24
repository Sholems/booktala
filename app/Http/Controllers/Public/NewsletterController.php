<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\NewsletterSubscribeRequest;
use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterSubscriber;
use App\Services\BrevoMarketingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(NewsletterSubscribeRequest $request)
    {
        $validated = $request->validated();
        $email = strtolower(trim($validated['email']));

        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if ($subscriber) {
            $subscriber->update([
                'name' => $validated['name'] ?? $subscriber->name,
                'status' => 'subscribed',
                'unsubscribed_at' => null,
                'subscribed_at' => now(),
            ]);
        } else {
            $subscriber = NewsletterSubscriber::create([
                'email' => $email,
                'name' => $validated['name'] ?? null,
                'status' => 'subscribed',
                'unsubscribe_token' => Str::random(32),
                'source' => $request->input('source', 'homepage_vip_banner'),
                'ip_address' => $request->ip(),
                'subscribed_at' => now(),
            ]);
        }

        // Send Welcome Email with promo voucher
        try {
            Mail::to($subscriber->email)->queue(new NewsletterWelcomeMail($subscriber));
        } catch (\Throwable $e) {
            Log::warning("Could not dispatch welcome email to {$subscriber->email}: ".$e->getMessage());
        }

        // Auto-sync to Brevo Contact list if Brevo is enabled
        try {
            app(BrevoMarketingService::class)->createOrUpdateSubscriber($subscriber);
        } catch (\Throwable $e) {
            Log::warning("Brevo contact sync error for {$subscriber->email}: ".$e->getMessage());
        }

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

    public function unsubscribe(Request $request, string $token)
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();

        // Also allow matching md5(email) fallback
        if (! $subscriber) {
            $subscriber = NewsletterSubscriber::all()->first(function ($sub) use ($token) {
                return md5(strtolower($sub->email)) === $token;
            });
        }

        if ($subscriber) {
            $subscriber->unsubscribe();
        }

        return view('public.newsletter-unsubscribed', [
            'subscriber' => $subscriber,
            'token' => $token,
        ]);
    }

    public function resubscribe(Request $request, string $token)
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();

        if (! $subscriber) {
            $subscriber = NewsletterSubscriber::all()->first(function ($sub) use ($token) {
                return md5(strtolower($sub->email)) === $token;
            });
        }

        if ($subscriber) {
            $subscriber->resubscribe();

            return redirect()->back()->with('status', 'You have been successfully resubscribed to BookTala VIP Club.');
        }

        return redirect('/')->with('error', 'Subscriber record not found.');
    }
}
