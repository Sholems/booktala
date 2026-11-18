<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Site;
use Illuminate\Http\Request;

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

    public function store(Request $request)
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

    public function update(Request $request, Site $site)
    {
        $site->update($this->validated($request));

        return redirect()->route('admin.sites.edit', $site)->with('success', 'Site updated');
    }

    /**
     * Move an existing property into this site (e.g. grouping flats into their building).
     */
    public function attachProperty(Request $request, Site $site)
    {
        $validated = $request->validate(['property_id' => 'required|exists:properties,id']);

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

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:2000',
            // Where staff clock in; both or neither.
            'latitude' => 'nullable|required_with:longitude|numeric|between:-90,90',
            'longitude' => 'nullable|required_with:latitude|numeric|between:-180,180',
            'geofence_radius_m' => 'nullable|integer|min:30|max:5000',
        ]);
        $data['country'] = ($data['country'] ?? null) ?: 'Nigeria';
        $data['geofence_radius_m'] = $data['geofence_radius_m'] ?? 200;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
