<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\OperationsAlert;
use App\Support\Supervision;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every stock change goes through here so the counts, the history and the
 * low-stock alert always agree.
 */
class InventoryService
{
    public function record(InventoryItem $item, string $type, float $quantity, User $by, ?int $propertyId = null, ?float $unitCost = null, ?string $note = null): StockMovement
    {
        if (!array_key_exists($type, StockMovement::TYPES)) {
            throw ValidationException::withMessages(['type' => 'Unknown stock movement.']);
        }
        if ($quantity < 0 || ($quantity == 0 && $type !== 'count')) {
            throw ValidationException::withMessages(['quantity' => 'Enter a quantity above zero.']);
        }
        if (in_array($type, ['to_laundry', 'from_laundry'], true) && !$item->tracks_laundry) {
            throw ValidationException::withMessages(['type' => $item->name . ' is not tracked through the laundry.']);
        }

        return DB::transaction(function () use ($item, $type, $quantity, $by, $propertyId, $unitCost, $note) {
            $item = InventoryItem::lockForUpdate()->findOrFail($item->id);

            [$onHand, $atLaundry] = match ($type) {
                'received' => [$item->on_hand + $quantity, $item->at_laundry],
                'used', 'discarded' => [$item->on_hand - $quantity, $item->at_laundry],
                'to_laundry' => [$item->on_hand - $quantity, $item->at_laundry + $quantity],
                'from_laundry' => [$item->on_hand + $quantity, $item->at_laundry - $quantity],
                'count' => [$quantity, $item->at_laundry],
            };

            if ($onHand < 0) {
                throw ValidationException::withMessages(['quantity' => 'Only ' . $item->quantityLabel($item->on_hand) . ' in stock. Record a stock count if the figure is wrong.']);
            }
            if ($atLaundry < 0) {
                throw ValidationException::withMessages(['quantity' => 'Only ' . $item->quantityLabel($item->at_laundry) . ' are at the laundry.']);
            }

            $movement = $item->movements()->create([
                'type' => $type,
                'quantity' => $quantity,
                'on_hand_after' => $onHand,
                'property_id' => $propertyId,
                'unit_cost' => $unitCost,
                'note' => $note,
                'user_id' => $by->id,
            ]);

            $item->update(['on_hand' => $onHand, 'at_laundry' => $atLaundry]);
            $this->checkLowStock($item->fresh('site'));

            return $movement;
        });
    }

    /**
     * Alert the site's supervisor once when stock falls to its reorder level;
     * re-arm when it is restocked above it.
     */
    private function checkLowStock(InventoryItem $item): void
    {
        if (!$item->isLow()) {
            if ($item->low_stock_alerted_at) {
                $item->update(['low_stock_alerted_at' => null]);
            }

            return;
        }
        if ($item->low_stock_alerted_at) {
            return;
        }

        foreach (Supervision::alertRecipients($item->site_id, 1) as $user) {
            $isHeadOffice = $user->hasAnyRole(['super_admin', 'admin']);
            $user->notify(new OperationsAlert(
                'Low stock: ' . $item->name . ' at ' . $item->site->name,
                $item->quantityLabel($item->on_hand) . ' left (reorder at ' . $item->quantityLabel($item->reorder_level) . ').',
                route(($isHeadOffice ? 'admin' : 'staff') . '.inventory.index', ['site' => $item->site_id]),
                'escalated',
                email: false,
            ));
        }
        $item->update(['low_stock_alerted_at' => now()]);
    }
}
