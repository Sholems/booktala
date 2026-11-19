<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Concerns\SiteScopedPortal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\InventoryStoreItemRequest;
use App\Http\Requests\Operations\InventoryStoreMovementRequest;
use App\Http\Requests\Operations\InventoryUpdateItemRequest;
use App\Models\InventoryItem;
use App\Models\Property;
use App\Models\StockMovement;
use App\Services\InventoryService;
use App\Services\SecurityService;
use Illuminate\Http\Request;

/**
 * Site stock: anyone at the site records use and laundry; supervisors and head
 * office add items, receive deliveries and correct counts.
 */
class InventoryController extends Controller
{
    use SiteScopedPortal;

    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $sites = $this->portalSites($user);
        $site = $this->selectedSite($request, $sites);
        if (! $site) {
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

    public function storeItem(InventoryStoreItemRequest $request)
    {
        $data = $request->validated();
        $this->ensureManages($request->user(), (int) $data['site_id']);

        $item = InventoryItem::create([
            'site_id' => $data['site_id'],
            'name' => $data['name'],
            'category' => $data['category'],
            'unit' => $data['unit'],
            'reorder_level' => $data['reorder_level'] ?? 0,
            'tracks_laundry' => $request->boolean('tracks_laundry'),
        ]);
        if (! empty($data['opening_stock'])) {
            $this->inventory->record($item, 'count', (float) $data['opening_stock'], $request->user(), note: 'Opening stock');
        }

        return back()->with('success', $item->name.' added');
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

    public function updateItem(InventoryUpdateItemRequest $request, InventoryItem $item)
    {
        $this->ensureManages($request->user(), $item->site_id);
        $data = $request->validated();
        $item->update($data + ['tracks_laundry' => $request->boolean('tracks_laundry'), 'is_active' => $request->boolean('is_active', true)]);

        return back()->with('success', 'Item updated');
    }

    public function storeMovement(InventoryStoreMovementRequest $request, InventoryItem $item)
    {
        $user = $request->user();
        $this->ensureSees($user, $item->site_id);
        $data = $request->validated();

        // Deliveries and corrected counts are for supervisors and head office.
        if (! StockMovement::TYPES[$data['type']][1]) {
            $this->ensureManages($user, $item->site_id);
        }

        $movement = $this->inventory->record(
            $item, $data['type'], (float) $data['quantity'], $user,
            isset($data['property_id']) ? (int) $data['property_id'] : null,
            isset($data['unit_cost']) ? (float) $data['unit_cost'] : null,
            $data['note'] ?? null,
        );

        return back()->with('success', $movement->label().': '.$item->quantityLabel($movement->quantity).' of '.$item->name.'. Now '.$item->quantityLabel($movement->on_hand_after).' on hand.');
    }
}
