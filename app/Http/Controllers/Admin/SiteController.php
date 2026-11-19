<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteAttachPropertyRequest;
use App\Http\Requests\Admin\SiteRequest;
use App\Models\Property;
use App\Models\Site;

/**
 * Sites: the buildings, estates and standalone apartments staff work at.
 */
class SiteController extends Controller
{
    public function index()
    {
        $sites = Site::withCount(['properties', 'activeAssignments'])
            ->orderBy('name')
            ->paginate(25);

        return view('admin.sites.index', compact('sites'));
    }

    public function create()
    {
        return view('admin.sites.form', ['site' => new Site(['country' => 'Nigeria', 'is_active' => true])]);
    }

    public function store(SiteRequest $request)
    {
        $site = Site::create($this->validated($request));

        return redirect()->route('admin.sites.edit', $site)->with('success', 'Site added. Now move properties into it and assign staff.');
    }

    public function edit(Site $site)
    {
        $site->load([
            'properties' => fn ($q) => $q->with('owner')->orderBy('title'),
            'activeAssignments' => fn ($q) => $q->with(['staff.user', 'position'])->orderBy('position_id'),
        ]);

        // Properties that could be moved into this site.
        $otherProperties = Property::where(fn ($q) => $q->whereNull('site_id')->orWhere('site_id', '!=', $site->id))
            ->with('site')
            ->orderBy('title')
            ->get();

        return view('admin.sites.form', compact('site', 'otherProperties'));
    }

    public function update(SiteRequest $request, Site $site)
    {
        $site->update($this->validated($request));

        return redirect()->route('admin.sites.edit', $site)->with('success', 'Site updated');
    }

    /**
     * Move an existing property into this site (e.g. grouping flats into their building).
     */
    public function attachProperty(SiteAttachPropertyRequest $request, Site $site)
    {
        $validated = $request->validated();

        $property = Property::findOrFail($validated['property_id']);
        $previous = $property->site;
        $property->update(['site_id' => $site->id]);

        // Tidy up the standalone site the property leaves behind, if nothing else uses it.
        if ($previous && $previous->id !== $site->id
            && ! $previous->properties()->exists() && ! $previous->assignments()->exists()) {
            $previous->delete();
        }

        return back()->with('success', $property->title.' moved to '.$site->name);
    }

    public function destroy(Site $site)
    {
        if ($site->properties()->exists() || $site->assignments()->exists()) {
            return back()->with('error', 'Only an empty site can be deleted: move its properties and end its staff assignments first, or mark it inactive.');
        }

        $site->delete();

        return redirect()->route('admin.sites.index')->with('success', 'Site deleted');
    }

    private function validated(SiteRequest $request): array
    {
        $data = $request->validated();
        $data['country'] = ($data['country'] ?? null) ?: 'Nigeria';
        $data['geofence_radius_m'] = $data['geofence_radius_m'] ?? 200;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
