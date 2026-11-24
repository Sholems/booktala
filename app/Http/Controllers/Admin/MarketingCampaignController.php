<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchCampaignJob;
use App\Mail\MarketingCampaignMail;
use App\Models\MarketingCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\Property;
use App\Services\BrevoMarketingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MarketingCampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = MarketingCampaign::with('creator')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $campaigns = $query->paginate(12)->withQueryString();

        $stats = [
            'total_subscribers' => NewsletterSubscriber::active()->count(),
            'total_campaigns' => MarketingCampaign::count(),
            'total_emails_sent' => MarketingCampaign::sum('sent_count'),
            'segment_counts' => MarketingCampaign::segmentCounts(),
        ];

        return view('admin.marketing.index', compact('campaigns', 'stats'));
    }

    public function create()
    {
        $properties = Property::with('site')->where('status', 'active')->orderBy('name')->get();
        $segmentCounts = MarketingCampaign::segmentCounts();

        return view('admin.marketing.create', compact('properties', 'segmentCounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'target_segment' => 'required|string|in:all_subscribers,past_guests,registered_users,active_affiliates,newsletter_only',
            'template_type' => 'required|string|in:luxury_showcase,promotional_deal,concierge_update',
            'headline' => 'nullable|string|max:255',
            'content_html' => 'required|string',
            'featured_property_ids' => 'nullable|array',
            'featured_property_ids.*' => 'exists:properties,id',
            'cta_text' => 'nullable|string|max:100',
            'cta_url' => 'nullable|string|max:255',
            'discount_code' => 'nullable|string|max:50',
            'hero_image_url' => 'nullable|url|max:500',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = 'draft';

        $campaign = MarketingCampaign::create($validated);

        return redirect()->route('admin.marketing.campaigns.show', $campaign)
            ->with('success', 'Marketing campaign created successfully as a draft.');
    }

    public function show(MarketingCampaign $campaign)
    {
        $campaign->load(['creator', 'logs' => fn ($q) => $q->latest()->take(50)]);
        $properties = $campaign->getFeaturedProperties();
        $segmentCounts = MarketingCampaign::segmentCounts();

        return view('admin.marketing.show', compact('campaign', 'properties', 'segmentCounts'));
    }

    public function edit(MarketingCampaign $campaign)
    {
        if ($campaign->status === 'sent' || $campaign->status === 'sending') {
            return redirect()->route('admin.marketing.campaigns.show', $campaign)
                ->with('error', 'Sent or active campaigns cannot be modified.');
        }

        $properties = Property::with('site')->where('status', 'active')->orderBy('name')->get();
        $segmentCounts = MarketingCampaign::segmentCounts();

        return view('admin.marketing.edit', compact('campaign', 'properties', 'segmentCounts'));
    }

    public function update(Request $request, MarketingCampaign $campaign)
    {
        if ($campaign->status === 'sent' || $campaign->status === 'sending') {
            return redirect()->route('admin.marketing.campaigns.show', $campaign)
                ->with('error', 'Sent or active campaigns cannot be modified.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'target_segment' => 'required|string|in:all_subscribers,past_guests,registered_users,active_affiliates,newsletter_only',
            'template_type' => 'required|string|in:luxury_showcase,promotional_deal,concierge_update',
            'headline' => 'nullable|string|max:255',
            'content_html' => 'required|string',
            'featured_property_ids' => 'nullable|array',
            'featured_property_ids.*' => 'exists:properties,id',
            'cta_text' => 'nullable|string|max:100',
            'cta_url' => 'nullable|string|max:255',
            'discount_code' => 'nullable|string|max:50',
            'hero_image_url' => 'nullable|url|max:500',
        ]);

        $campaign->update($validated);

        return redirect()->route('admin.marketing.campaigns.show', $campaign)
            ->with('success', 'Marketing campaign updated successfully.');
    }

    public function preview(MarketingCampaign $campaign)
    {
        $properties = $campaign->getFeaturedProperties();
        $mockRecipient = [
            'name' => Auth::user()->name ?? 'VIP Guest',
            'email' => Auth::user()->email ?? 'guest@example.com',
            'unsubscribe_url' => route('newsletter.unsubscribe', ['token' => 'preview-sample']),
        ];

        return view('emails.marketing.campaign', [
            'campaign' => $campaign,
            'recipient' => $mockRecipient,
            'properties' => $properties,
            'preview_text' => $campaign->preview_text,
            'unsubscribe_url' => $mockRecipient['unsubscribe_url'],
        ]);
    }

    public function sendTest(Request $request, MarketingCampaign $campaign)
    {
        $targetEmail = $request->input('test_email') ?: Auth::user()->email;

        if (! filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'Invalid test destination email address.');
        }

        $properties = $campaign->getFeaturedProperties();
        $recipient = [
            'name' => Auth::user()->name ?? 'Admin Tester',
            'email' => $targetEmail,
            'unsubscribe_url' => route('newsletter.unsubscribe', ['token' => 'admin-test']),
        ];

        try {
            Mail::to($targetEmail)->send(new MarketingCampaignMail($campaign, $recipient, $properties));

            return redirect()->back()->with('success', "Test email successfully dispatched to [{$targetEmail}] via Brevo!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed sending test email: '.$e->getMessage());
        }
    }

    public function dispatchCampaign(MarketingCampaign $campaign)
    {
        if ($campaign->status === 'sent' || $campaign->status === 'sending') {
            return redirect()->back()->with('error', 'This campaign is already sending or has been sent.');
        }

        DispatchCampaignJob::dispatch($campaign);

        return redirect()->route('admin.marketing.campaigns.show', $campaign)
            ->with('success', 'Campaign queued for transmission! Batch delivery via Brevo has started.');
    }

    public function destroy(MarketingCampaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('admin.marketing.campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    public function subscribers(Request $request)
    {
        $query = NewsletterSubscriber::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $subscribers = $query->latest('subscribed_at')->paginate(20)->withQueryString();

        $stats = [
            'total' => NewsletterSubscriber::count(),
            'active' => NewsletterSubscriber::where('status', 'subscribed')->count(),
            'unsubscribed' => NewsletterSubscriber::where('status', 'unsubscribed')->count(),
        ];

        return view('admin.marketing.subscribers', compact('subscribers', 'stats'));
    }

    public function storeSubscriber(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:newsletter_subscribers,email',
            'name' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',
        ]);

        $sub = NewsletterSubscriber::create([
            'email' => strtolower(trim($validated['email'])),
            'name' => $validated['name'] ?? null,
            'source' => $validated['source'] ?? 'admin_manual',
            'status' => 'subscribed',
            'unsubscribe_token' => Str::random(32),
            'subscribed_at' => now(),
        ]);

        // Auto sync to Brevo
        try {
            app(BrevoMarketingService::class)->createOrUpdateSubscriber($sub);
        } catch (\Throwable $e) {
        }

        return redirect()->back()->with('success', 'Subscriber added successfully.');
    }

    public function toggleSubscriber(NewsletterSubscriber $subscriber)
    {
        if ($subscriber->status === 'subscribed') {
            $subscriber->unsubscribe();
            $msg = 'Subscriber status set to unsubscribed.';
        } else {
            $subscriber->resubscribe();
            $msg = 'Subscriber resubscribed successfully.';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function destroySubscriber(NewsletterSubscriber $subscriber)
    {
        $subscriber->delete();

        return redirect()->back()->with('success', 'Subscriber removed successfully.');
    }
}
