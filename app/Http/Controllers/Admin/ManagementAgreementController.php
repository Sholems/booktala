<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ManagementAgreementRequest;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\Site;
use App\Models\Unit;
use App\Services\Finance\AgreementResolver;
use App\Support\PropertyAccess;
use Illuminate\Http\Request;

class ManagementAgreementController extends Controller
{
    public function __construct(private AgreementResolver $resolver) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $query = ManagementAgreement::with(['unit.site', 'owner', 'creator', 'reviewer'])
            ->when(! PropertyAccess::seesAll($user), function ($q) use ($user) {
                $q->whereIn('unit_id', PropertyAccess::visiblePropertyIds($user));
            })
            ->when($request->filled('unit_id'), fn ($q) => $q->where('unit_id', $request->integer('unit_id')))
            ->when($request->filled('owner_id'), fn ($q) => $q->where('owner_id', $request->integer('owner_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('effective_from');

        $agreements = $query->paginate(25)->withQueryString();

        $units = Unit::query()->visibleTo($user)->orderBy('title')->get();
        $owners = Owner::query()->orderBy('name')->get();

        return view('admin.finance.agreements.index', [
            'agreements' => $agreements,
            'units' => $units,
            'owners' => $owners,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $units = Unit::query()->visibleTo($user)->with(['owner', 'site'])->orderBy('title')->get();
        $owners = Owner::query()->orderBy('name')->get();

        return view('admin.finance.agreements.create', [
            'units' => $units,
            'owners' => $owners,
            'selectedUnitId' => $request->integer('unit_id'),
        ]);
    }

    public function store(ManagementAgreementRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        try {
            $agreement = $this->resolver->create($data);

            return redirect()->route('admin.agreements.show', $agreement)
                ->with('success', 'Management agreement drafted successfully.');
        } catch (\DomainException $exception) {
            return back()->withInput()->withErrors(['effective_from' => $exception->getMessage()]);
        }
    }

    public function show(ManagementAgreement $agreement)
    {
        PropertyAccess::authorize(auth()->user(), $agreement->unit_id);

        $agreement->load(['unit.site', 'owner', 'creator', 'reviewer', 'financeEntries' => fn ($q) => $q->latest()->limit(50)]);

        return view('admin.finance.agreements.show', [
            'agreement' => $agreement,
        ]);
    }

    public function review(ManagementAgreement $agreement)
    {
        PropertyAccess::authorize(auth()->user(), $agreement->unit_id);

        try {
            $this->resolver->review($agreement, auth()->user());

            return back()->with('success', 'Management agreement reviewed and activated.');
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function retire(ManagementAgreement $agreement)
    {
        PropertyAccess::authorize(auth()->user(), $agreement->unit_id);

        try {
            $this->resolver->retire($agreement);

            return back()->with('success', 'Management agreement retired.');
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function destroy(ManagementAgreement $agreement)
    {
        PropertyAccess::authorize(auth()->user(), $agreement->unit_id);

        if ($agreement->status !== ManagementAgreement::STATUS_DRAFT) {
            return back()->with('error', 'Only draft agreements can be deleted.');
        }

        $agreement->delete();

        return redirect()->route('admin.agreements.index')->with('success', 'Draft agreement deleted.');
    }

    public function audit(Request $request)
    {
        $user = $request->user();
        $siteId = $request->filled('site_id') ? $request->integer('site_id') : null;

        if ($siteId !== null && ! PropertyAccess::seesAll($user)) {
            $visibleSites = PropertyAccess::activeSiteIds($user)->pluck('site_id')->all();
            if (! in_array($siteId, $visibleSites, true)) {
                abort(404);
            }
        }

        $auditResult = $this->resolver->coverageAudit($siteId, $user);
        $sites = PropertyAccess::seesAll($user)
            ? Site::orderBy('name')->get()
            : Site::whereIn('id', PropertyAccess::activeSiteIds($user))->orderBy('name')->get();

        return view('admin.finance.agreements.audit', [
            'audit' => $auditResult,
            'sites' => $sites,
            'selectedSiteId' => $siteId,
        ]);
    }
}
