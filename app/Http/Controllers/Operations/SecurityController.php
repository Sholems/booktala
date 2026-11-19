<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Concerns\SiteScopedPortal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\SecurityResolveIncidentRequest;
use App\Http\Requests\Operations\SecurityStoreCheckpointRequest;
use App\Http\Requests\Operations\SecurityStoreIncidentRequest;
use App\Http\Requests\Operations\SecurityStoreVisitorRequest;
use App\Http\Requests\Operations\SecurityUpdatePatrolSettingsRequest;
use App\Models\Incident;
use App\Models\PatrolCheckpoint;
use App\Models\Property;
use App\Models\Site;
use App\Models\VisitorLog;
use App\Services\SecurityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Site security for gate staff, front desk, supervisors and head office:
 * visitor log, incidents and patrol checkpoints.
 */
class SecurityController extends Controller
{
    use SiteScopedPortal;

    public function __construct(private SecurityService $security) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $sites = $this->portalSites($user);
        $site = $this->selectedSite($request, $sites);
        if (! $site) {
            return view('operations.no-site', $this->viewData($request, ['title' => 'Security']));
        }

        $date = CarbonImmutable::parse($request->query('date', today()->toDateString()));

        return view('operations.security.index', $this->viewData($request, [
            'sites' => $sites,
            'site' => $site,
            'date' => $date,
            'onSite' => VisitorLog::where('site_id', $site->id)->onSite()->with(['property', 'loggedBy'])->orderBy('arrived_at')->get(),
            'visitors' => VisitorLog::where('site_id', $site->id)->whereDate('arrived_at', $date)->whereNotNull('left_at')->with('property')->latest('arrived_at')->get(),
            'incidents' => Incident::where('site_id', $site->id)
                ->where(fn ($q) => $q->where('status', 'open')->orWhere('occurred_at', '>=', now()->subDays(7)))
                ->with(['reportedBy', 'property'])
                ->orderByRaw("CASE status WHEN 'open' THEN 0 ELSE 1 END")
                ->latest('occurred_at')
                ->get(),
            'checkpoints' => PatrolCheckpoint::where('site_id', $site->id)->where('is_active', true)->with('lastScan.staff.user')->orderBy('name')->get(),
            'properties' => Property::where('site_id', $site->id)->orderBy('title')->get(),
            'canManage' => SecurityService::canManage($user, $site->id),
        ]));
    }

    // --- Visitors -------------------------------------------------------------------------

    public function storeVisitor(SecurityStoreVisitorRequest $request)
    {
        $data = $request->validated();
        $this->ensureSees($request->user(), (int) $data['site_id']);
        $this->ensurePropertyAtSite($data['property_id'] ?? null, (int) $data['site_id']);

        VisitorLog::create($data + ['arrived_at' => now(), 'logged_by' => $request->user()->id]);

        return back()->with('success', $data['visitor_name'].' signed in');
    }

    public function signOutVisitor(Request $request, VisitorLog $visitor)
    {
        $this->ensureSees($request->user(), $visitor->site_id);
        if ($visitor->left_at) {
            return back()->with('error', $visitor->visitor_name.' already signed out.');
        }

        $visitor->update(['left_at' => now(), 'signed_out_by' => $request->user()->id]);

        return back()->with('success', $visitor->visitor_name.' signed out');
    }

    // --- Incidents -----------------------------------------------------------------------

    public function storeIncident(SecurityStoreIncidentRequest $request)
    {
        $data = $request->validated();
        $this->ensureSees($request->user(), (int) $data['site_id']);
        $this->ensurePropertyAtSite($data['property_id'] ?? null, (int) $data['site_id']);

        if ($request->hasFile('photo')) {
            // Evidence photos are private: served only through the app.
            $data['photo_path'] = $request->file('photo')->store('incidents', 'local');
        }
        unset($data['photo']);

        $incident = Incident::create($data + ['reported_by' => $request->user()->id, 'status' => 'open']);
        $this->security->alertIncident($incident->load('site', 'reportedBy'));

        return redirect()->route($this->portal($request).'.security.incidents.show', $incident)
            ->with('success', 'Incident recorded'.($incident->isSerious() ? '. Your supervisor and head office have been alerted.' : '.'));
    }

    public function showIncident(Request $request, Incident $incident)
    {
        $this->ensureSees($request->user(), $incident->site_id);
        $incident->load(['site', 'property', 'reportedBy', 'resolvedBy']);

        return view('operations.security.incident', $this->viewData($request, [
            'incident' => $incident,
            'canManage' => SecurityService::canManage($request->user(), $incident->site_id),
        ]));
    }

    public function incidentPhoto(Request $request, Incident $incident)
    {
        $this->ensureSees($request->user(), $incident->site_id);
        abort_unless($incident->photo_path && Storage::disk('local')->exists($incident->photo_path), 404);

        return Storage::disk('local')->response($incident->photo_path);
    }

    public function resolveIncident(SecurityResolveIncidentRequest $request, Incident $incident)
    {
        $this->ensureManages($request->user(), $incident->site_id);
        $data = $request->validated();

        if ($incident->status === 'resolved') {
            return back()->with('error', 'This incident is already resolved.');
        }
        $incident->update(['status' => 'resolved', 'resolution' => $data['resolution'], 'resolved_by' => $request->user()->id, 'resolved_at' => now()]);

        return back()->with('success', 'Incident resolved');
    }

    // --- Patrols ---------------------------------------------------------------------------

    public function updatePatrolSettings(SecurityUpdatePatrolSettingsRequest $request, Site $site)
    {
        $this->ensureManages($request->user(), $site->id);
        $data = $request->validated();
        $site->update($data);

        return back()->with('success', $data['patrol_interval_minutes'] ? 'Patrol schedule saved' : 'Patrol checks turned off for '.$site->name);
    }

    public function storeCheckpoint(SecurityStoreCheckpointRequest $request)
    {
        $data = $request->validated();
        $this->ensureManages($request->user(), (int) $data['site_id']);

        PatrolCheckpoint::create($data);

        return back()->with('success', 'Checkpoint added. Print its QR code and fix it at the spot.');
    }

    public function removeCheckpoint(Request $request, PatrolCheckpoint $checkpoint)
    {
        $this->ensureManages($request->user(), $checkpoint->site_id);
        $checkpoint->update(['is_active' => false]);

        return back()->with('success', 'Checkpoint removed');
    }

    public function printCheckpoints(Request $request)
    {
        $sites = $this->portalSites($request->user());
        $site = $this->selectedSite($request, $sites) ?? abort(404);
        $this->ensureManages($request->user(), $site->id);

        return view('operations.security.print-checkpoints', [
            'site' => $site,
            'checkpoints' => PatrolCheckpoint::where('site_id', $site->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    private function ensurePropertyAtSite(?string $propertyId, int $siteId): void
    {
        if ($propertyId && (int) Property::whereKey($propertyId)->value('site_id') !== $siteId) {
            throw ValidationException::withMessages(['property_id' => 'That property is not at this site.']);
        }
    }
}
