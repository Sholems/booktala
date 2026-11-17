<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Concerns\SiteScopedPortal;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Property;
use App\Models\StockMovement;
use App\Services\InventoryService;
use App\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Site stock: anyone at the site records use and laundry; supervisors and head
 * office add items, receive deliveries and correct counts.
 */
class InventoryController extends Controller
{
    use SiteScopedPortal;

    public function __construct(private InventoryService $inventory)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $sites = $this->portalSites($user);
        $site = $this->selectedSite($request, $sites);
        if (!$site) {
            return view('operations.no-site', $this->viewData($request, ['title' => 'Inventory']));
        }

        $items = InventoryItem::where('site_id', $site->id)->where('is_active', true)
            ->orderByRaw('CASE WHEN reorder_level > 0 AND on_hand <= reorder_level THEN 0 ELSE 1 END')
            ->orderBy('category')->orderBy('name')
            ->get();

        return view('operations.inventory.index', $this->viewData($request, [
            'sites' => $sites,
            'site' => $site,
            'items' => $items,
            'properties' => Property::where('site_id', $site->id)->orderBy('title')->get(),
            'canManage' => SecurityService::canManage($user, $site->id),
            'recent' => StockMovement::whereIn('inventory_item_id', $items->pluck('id'))->with(['item', 'user', 'property'])->latest()->limit(15)->get(),
        ]));
    }

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'name' => ['required', 'string', 'max:100', Rule::unique('inventory_items')->where('site_id', $request->integer('site_id'))],
            'category' => ['required', Rule::in(array_keys(InventoryItem::CATEGORIES))],
            'unit' => 'required|string|max:20',
            'reorder_level' => 'nullable|numeric|min:0',
            'opening_stock' => 'nullable|numeric|min:0',
        ], ['name.unique' => 'This site already has an item with that name.']);
        $this->ensureManages($request->user(), (int) $data['site_id']);

        $item = InventoryItem::create([
            'site_id' => $data['site_id'],
            'name' => $data['name'],
            'category' => $data['category'],
            'unit' => $data['unit'],
            'reorder_level' => $data['reorder_level'] ?? 0,
            'tracks_laundry' => $request->boolean('tracks_laundry'),
        ]);
        if (!empty($data['opening_stock'])) {
            $this->inventory->record($item, 'count', (float) $data['opening_stock'], $request->user(), note: 'Opening stock');
        }

        return back()->with('success', $item->name . ' added');
    }

    public function show(Request $request, InventoryItem $item)
    {
        $this->ensureSees($request->user(), $item->site_id);

        return view('operations.inventory.show', $this->viewData($request, [
            'item' => $item->load('site'),
            'movements' => $item->movements()->with(['user', 'property'])->paginate(40),
            'canManage' => SecurityService::canManage($request->user(), $item->site_id),
        ]));
    }

    public function updateItem(Request $request, InventoryItem $item)
    {
        $this->ensureManages($request->user(), $item->site_id);
        $data = $request->validate([
            'reorder_level' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
        ]);
        $item->update($data + ['tracks_laundry' => $request->boolean('tracks_laundry'), 'is_active' => $request->boolean('is_active', true)]);

        return back()->with('success', 'Item updated');
    }

    public function storeMovement(Request $request, InventoryItem $item)
    {
        $user = $request->user();
        $this->ensureSees($user, $item->site_id);
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(StockMovement::TYPES))],
            'quantity' => 'required|numeric|min:0|max:1000000',
            'property_id' => ['nullable', Rule::exists('properties', 'id')->where('site_id', $item->site_id)],
            'unit_cost' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        // Deliveries and corrected counts are for supervisors and head office.
        if (!StockMovement::TYPES[$data['type']][1]) {
            $this->ensureManages($user, $item->site_id);
        }

        $movement = $this->inventory->record(
            $item, $data['type'], (float) $data['quantity'], $user,
            isset($data['property_id']) ? (int) $data['property_id'] : null,
            isset($data['unit_cost']) ? (float) $data['unit_cost'] : null,
            $data['note'] ?? null,
        );

        return back()->with('success', $movement->label() . ': ' . $item->quantityLabel($movement->quantity) . ' of ' . $item->name . '. Now ' . $item->quantityLabel($movement->on_hand_after) . ' on hand.');
    }
}
