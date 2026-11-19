<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use App\Models\Site;
use App\Models\Unit;
use App\Services\PricingService;
use App\Support\PropertyAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PricingRuleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $rulesQuery = PricingRule::with(['unit.site', 'site'])
            ->when(! PropertyAccess::seesAll($user), function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->whereIn('property_id', PropertyAccess::visiblePropertyIds($user))
                        ->orWhereIn('site_id', PropertyAccess::activeSiteIds($user));
                });
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('unit_id'), fn ($q) => $q->where('property_id', $request->integer('unit_id')))
            ->orderByDesc('priority')
            ->orderByDesc('start_date');

        $rules = $rulesQuery->paginate(20)->withQueryString();

        $units = PropertyAccess::seesAll($user)
            ? Unit::query()->active()->orderBy('title')->get()
            : Unit::query()->active()->whereIn('id', PropertyAccess::visiblePropertyIds($user))->orderBy('title')->get();

        $sites = PropertyAccess::seesAll($user)
            ? Site::orderBy('name')->get()
            : Site::whereIn('id', PropertyAccess::activeSiteIds($user))->orderBy('name')->get();

        return view('admin.pricing.index', compact('rules', 'units', 'sites'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:seasonal,weekend,event,length_of_stay,promo',
            'property_id' => 'nullable|exists:properties,id',
            'site_id' => 'nullable|exists:sites,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'adjustment_type' => 'required|in:fixed_override,percentage_markup,percentage_discount,fixed_markup',
            'adjustment_value' => 'nullable|numeric|min:0',
            'price_override' => 'nullable|numeric|min:0',
            'min_nights' => 'nullable|integer|min:1|max:365',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|min:1|max:7',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);

        if (! empty($data['property_id'])) {
            PropertyAccess::authorize($request->user(), $data['property_id']);
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['priority'] = $data['priority'] ?? 0;

        if ($data['adjustment_type'] === 'fixed_override') {
            $data['price_override'] = $data['adjustment_value'] ?? $data['price_override'];
        }

        PricingRule::create($data);

        return redirect()->route('admin.pricing-rules.index')->with('success', 'Pricing rule created successfully.');
    }

    public function update(Request $request, PricingRule $pricingRule)
    {
        if ($pricingRule->property_id) {
            PropertyAccess::authorize($request->user(), $pricingRule->property_id);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:seasonal,weekend,event,length_of_stay,promo',
            'property_id' => 'nullable|exists:properties,id',
            'site_id' => 'nullable|exists:sites,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'adjustment_type' => 'required|in:fixed_override,percentage_markup,percentage_discount,fixed_markup',
            'adjustment_value' => 'nullable|numeric|min:0',
            'price_override' => 'nullable|numeric|min:0',
            'min_nights' => 'nullable|integer|min:1|max:365',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|min:1|max:7',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);

        if (! empty($data['property_id'])) {
            PropertyAccess::authorize($request->user(), $data['property_id']);
        }

        $data['is_active'] = $request->boolean('is_active', true);
        if ($data['adjustment_type'] === 'fixed_override') {
            $data['price_override'] = $data['adjustment_value'] ?? $data['price_override'];
        }

        $pricingRule->update($data);

        return redirect()->route('admin.pricing-rules.index')->with('success', 'Pricing rule updated successfully.');
    }

    public function toggle(PricingRule $pricingRule, Request $request)
    {
        if ($pricingRule->property_id) {
            PropertyAccess::authorize($request->user(), $pricingRule->property_id);
        }

        $pricingRule->update(['is_active' => ! $pricingRule->is_active]);

        return back()->with('success', "Rule '{$pricingRule->name}' ".($pricingRule->is_active ? 'activated' : 'paused').'.');
    }

    public function destroy(PricingRule $pricingRule, Request $request)
    {
        if ($pricingRule->property_id) {
            PropertyAccess::authorize($request->user(), $pricingRule->property_id);
        }

        $pricingRule->delete();

        return redirect()->route('admin.pricing-rules.index')->with('success', 'Pricing rule deleted.');
    }

    public function simulator(Request $request, PricingService $pricingService)
    {
        $user = $request->user();

        $units = PropertyAccess::seesAll($user)
            ? Unit::query()->active()->orderBy('title')->get()
            : Unit::query()->active()->whereIn('id', PropertyAccess::visiblePropertyIds($user))->orderBy('title')->get();

        $selectedUnitId = $request->integer('unit_id') ?: $units->first()?->id;
        $selectedUnit = $units->firstWhere('id', $selectedUnitId);

        $monthStr = $request->string('month')->toString() ?: now()->format('Y-m');
        $startOfMonth = Carbon::parse($monthStr.'-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $calendar = [];
        if ($selectedUnit) {
            $calendar = $pricingService->getDailyPriceCalendar($selectedUnit, $startOfMonth, $endOfMonth);
        }

        return view('admin.pricing.simulator', compact('units', 'selectedUnit', 'monthStr', 'calendar'));
    }
}
