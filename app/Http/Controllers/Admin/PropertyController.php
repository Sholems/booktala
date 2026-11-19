<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PropertyBulkActionRequest;
use App\Http\Requests\Admin\PropertyStoreRequest;
use App\Http\Requests\Admin\PropertyUpdateRequest;
use App\Models\Amenity;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::with(['images', 'amenities']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('featured')) {
            $query->where('is_featured', $request->featured);
        }

        $properties = $query->latest()->paginate(20)->appends($request->query());

        return view('admin.properties.index', compact('properties'));
    }

    public function create()
    {
        $amenities = Amenity::all()->groupBy('category');

        return view('admin.properties.create', compact('amenities') + $this->ownershipOptions());
    }

    public function store(PropertyStoreRequest $request)
    {
        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['title']);
        $validated['created_by'] = auth()->check() ? auth()->user()->id : 1;
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['status'] = $validated['status'] ?? 'active';

        $property = Property::create($validated);

        if ($request->amenities) {
            $property->amenities()->attach($request->amenities);
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('property-images', 'public');
                PropertyImage::create([
                    'property_id' => $property->id,
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()->route('admin.properties.index')->with('success', 'Property created');
    }

    public function show(Property $property)
    {
        $property->load(['images', 'amenities']);

        return view('admin.properties.show', compact('property'));
    }

    public function edit(Property $property)
    {
        $amenities = Amenity::all()->groupBy('category');
        $property->load(['images', 'amenities']);

        return view('admin.properties.edit', compact('property', 'amenities') + $this->ownershipOptions());
    }

    public function update(PropertyUpdateRequest $request, Property $property)
    {
        $validated = $request->validated();

        if ($request->slug !== $property->slug) {
            $validated['slug'] = Str::slug($request->slug);
        }
        $validated['is_featured'] = $request->boolean('is_featured');

        // Keep the current owner and site when the fields are left blank.
        $validated = array_filter($validated, fn ($v, $k) => ! in_array($k, ['owner_id', 'site_id'], true) || $v !== null, ARRAY_FILTER_USE_BOTH);
        $property->update($validated);

        $property->amenities()->sync($request->input('amenities', []));

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('property-images', 'public');
                PropertyImage::create([
                    'property_id' => $property->id,
                    'image_path' => $path,
                    'is_primary' => false,
                    'sort_order' => $property->images->count() + $index,
                ]);
            }
        }

        if ($request->has('delete_images')) {
            PropertyImage::whereIn('id', $request->delete_images)->delete();
        }

        if ($request->has('primary_image')) {
            $property->images()->update(['is_primary' => false]);
            PropertyImage::where('id', $request->primary_image)->update(['is_primary' => true]);
        }

        return back()->with('success', 'Property updated');
    }

    public function destroy(Property $property)
    {
        $property->delete();

        return redirect()->route('admin.properties.index')->with('success', 'Property deleted');
    }

    public function updateStatus(Request $request, Property $property)
    {
        $property->update(['status' => $request->status]);

        return back()->with('success', 'Status updated');
    }

    public function toggleFeatured(Property $property)
    {
        $property->update(['is_featured' => ! $property->is_featured]);

        return back()->with('success', 'Featured status updated');
    }

    public function bulkAction(PropertyBulkActionRequest $request)
    {
        $request->validated();

        $ids = $request->property_ids;
        $action = $request->action;

        switch ($action) {
            case 'active':
                Property::whereIn('id', $ids)->update(['status' => 'active']);
                $message = 'Properties set to active';
                break;
            case 'inactive':
                Property::whereIn('id', $ids)->update(['status' => 'inactive']);
                $message = 'Properties set to inactive';
                break;
            case 'featured':
                Property::whereIn('id', $ids)->update(['is_featured' => true]);
                $message = 'Properties marked as featured';
                break;
            case 'delete':
                Property::whereIn('id', $ids)->delete();
                $message = 'Properties deleted';
                break;
            default:
                $message = 'Invalid action';
        }

        return back()->with('success', $message);
    }

    /**
     * Owners and sites for the property form.
     */
    private function ownershipOptions(): array
    {
        return [
            'owners' => Owner::where('is_active', true)->orderByDesc('is_company_owned')->orderBy('name')->get(),
            'sites' => Site::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
