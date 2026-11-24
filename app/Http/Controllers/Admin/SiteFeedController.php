<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteMessage;
use App\Models\Unit;
use App\Services\CommunicationAlertService;
use Illuminate\Http\Request;

class SiteFeedController extends Controller
{
    public function index(Request $request)
    {
        $sites = Site::where('is_active', true)->orderBy('name')->get();
        if ($sites->isEmpty()) {
            return view('admin.site-feed.index', [
                'sites' => collect(),
                'currentSite' => null,
                'messages' => collect(),
                'units' => collect(),
                'selectedCategory' => 'all',
                'categories' => SiteMessage::CATEGORIES,
            ]);
        }

        $selectedSiteId = (int) $request->query('site_id', $sites->first()->id);
        $currentSite = $sites->firstWhere('id', $selectedSiteId) ?? $sites->first();
        $category = $request->query('category', 'all');

        $messages = SiteMessage::with(['user', 'unit', 'pinnedByUser'])
            ->forSite($currentSite->id)
            ->byCategory($category)
            ->orderByDesc('pinned')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $units = Unit::where('site_id', $currentSite->id)->where('status', 'active')->orderBy('title')->get();

        return view('admin.site-feed.index', [
            'sites' => $sites,
            'currentSite' => $currentSite,
            'messages' => $messages,
            'units' => $units,
            'selectedCategory' => $category,
            'categories' => SiteMessage::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'category' => 'required|string|in:'.implode(',', array_keys(SiteMessage::CATEGORIES)),
            'message' => 'required|string|max:2000',
            'unit_id' => 'nullable|exists:properties,id',
            'pinned' => 'nullable|boolean',
        ]);

        $pinned = ! empty($validated['pinned']);

        $siteMessage = SiteMessage::create([
            'site_id' => $validated['site_id'],
            'user_id' => $request->user()->id,
            'unit_id' => $validated['unit_id'] ?? null,
            'category' => $validated['category'],
            'message' => trim($validated['message']),
            'pinned' => $pinned,
            'pinned_at' => $pinned ? now() : null,
            'pinned_by' => $pinned ? $request->user()->id : null,
        ]);

        app(CommunicationAlertService::class)->notifySiteMessage($siteMessage, $request->user());

        return back()->with('success', 'Operational update posted to site feed.');
    }

    public function togglePin(SiteMessage $message)
    {
        $newPinned = ! $message->pinned;
        $message->update([
            'pinned' => $newPinned,
            'pinned_at' => $newPinned ? now() : null,
            'pinned_by' => $newPinned ? auth()->id() : null,
        ]);

        return back()->with('success', $newPinned ? 'Message pinned to top of feed.' : 'Message unpinned.');
    }

    public function destroy(SiteMessage $message)
    {
        $message->delete();

        return back()->with('success', 'Message removed from feed.');
    }
}
