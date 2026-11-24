<?php

namespace App\Http\Controllers\Staff;

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
        $staff = $request->user()->staff;
        abort_unless($staff && $staff->is_active, 403, 'Your staff profile is not active.');

        $assignedSites = $staff->activeSites()->get();
        abort_if($assignedSites->isEmpty(), 403, 'You are not assigned to any active site.');

        $selectedSiteId = (int) $request->query('site_id', $assignedSites->first()->id);
        $currentSite = $assignedSites->firstWhere('id', $selectedSiteId) ?? $assignedSites->first();

        $category = $request->query('category', 'all');

        $messages = SiteMessage::with(['user', 'unit', 'pinnedByUser'])
            ->forSite($currentSite->id)
            ->byCategory($category)
            ->orderByDesc('pinned')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $units = Unit::where('site_id', $currentSite->id)->where('status', 'active')->orderBy('title')->get();
        $isSupervisor = $staff->isSupervisorAt($currentSite);

        return view('staff.team.feed', [
            'sites' => $assignedSites,
            'currentSite' => $currentSite,
            'messages' => $messages,
            'units' => $units,
            'selectedCategory' => $category,
            'categories' => SiteMessage::CATEGORIES,
            'isSupervisor' => $isSupervisor,
        ]);
    }

    public function store(Request $request)
    {
        $staff = $request->user()->staff;
        abort_unless($staff && $staff->is_active, 403);

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'category' => 'required|string|in:'.implode(',', array_keys(SiteMessage::CATEGORIES)),
            'message' => 'required|string|max:2000',
            'unit_id' => 'nullable|exists:properties,id',
            'pinned' => 'nullable|boolean',
        ]);

        $assignedSiteIds = $staff->activeSites()->pluck('sites.id')->all();
        abort_unless(in_array((int) $validated['site_id'], $assignedSiteIds, true), 403, 'You cannot post to unassigned sites.');

        $isSupervisor = $staff->isSupervisorAt(Site::findOrFail($validated['site_id']));
        $pinned = $isSupervisor && ! empty($validated['pinned']);

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

    public function togglePin(Request $request, SiteMessage $message)
    {
        $staff = $request->user()->staff;
        abort_unless($staff && $staff->isSupervisorAt($message->site), 403, 'Only supervisors can pin messages.');

        $newPinned = ! $message->pinned;
        $message->update([
            'pinned' => $newPinned,
            'pinned_at' => $newPinned ? now() : null,
            'pinned_by' => $newPinned ? $request->user()->id : null,
        ]);

        return back()->with('success', $newPinned ? 'Message pinned to top of feed.' : 'Message unpinned.');
    }
}
