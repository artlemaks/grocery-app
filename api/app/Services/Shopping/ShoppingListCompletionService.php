<?php

namespace App\Services\Shopping;

use App\Enums\InventoryLocation;
use App\Enums\InventoryStatus;
use App\Enums\ShoppingListStatus;
use App\Models\InventoryItem;
use App\Models\ShoppingList;
use App\Services\Inventory\BestBeforeCalculator;
use Illuminate\Support\Collection;

/**
 * "Complete shopping" (spec §5.5): convert every checked item into an active inventory lot
 * at remaining = 1.0 with a sealed best-before from the ingredient's shelf life, mark the list
 * Completed, and leave unchecked items in place to roll over. Shared by the API and web controllers.
 */
class ShoppingListCompletionService
{
    public function __construct(private BestBeforeCalculator $bestBefore) {}

    /**
     * @return Collection<int, InventoryItem> the lots created
     */
    public function complete(ShoppingList $list): Collection
    {
        $list->loadMissing('items.ingredient');
        $today = now();

        $lots = $list->items
            ->filter(fn (\App\Models\ShoppingListItem $item) => $item->is_checked)
            ->map(function (\App\Models\ShoppingListItem $item) use ($list, $today) {
                $sealed = $this->bestBefore->sealedFrom($today->copy(), $item->ingredient?->shelf_life_sealed_days);

                return InventoryItem::create([
                    'household_id' => $list->household_id,
                    'ingredient_id' => $item->ingredient_id,
                    'location' => InventoryLocation::Pantry,
                    'remaining' => 1.00,
                    'purchased_on' => $today->toDateString(),
                    'status' => InventoryStatus::Active,
                    'sealed_best_before' => $sealed,
                    'effective_best_before' => $this->bestBefore->effective($sealed, null, null),
                ]);
            })
            ->values();

        $list->update(['status' => ShoppingListStatus::Completed]);

        return $lots;
    }
}
