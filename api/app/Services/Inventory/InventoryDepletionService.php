<?php

namespace App\Services\Inventory;

use App\Enums\InventoryStatus;
use App\Models\InventoryItem;
use App\Models\UsageLog;

/**
 * Depletes inventory as the household cooks (spec §5.6, ADR-0001/0003):
 *  - decrement a lot's fraction remaining;
 *  - first usage auto-opens a lot that tracks opening, starting the opened clock;
 *  - recompute effective best-before (earlier of sealed vs opened);
 *  - a lot at 0 is marked Used;
 *  - when depleting "an ingredient", pick the soonest-to-expire lot (FIFO by best-before).
 */
class InventoryDepletionService
{
    public function __construct(private BestBeforeCalculator $bestBefore) {}

    /**
     * Log usage against a specific lot and apply the depletion side effects.
     */
    public function logUsage(InventoryItem $item, float $amount, ?int $mealPlanEntryId = null): UsageLog
    {
        $item->loadMissing('ingredient');

        // First usage of a tracked item opens it and starts the opened clock.
        if (! $item->is_opened && $item->ingredient?->requires_open_tracking) {
            $item->is_opened = true;
            $item->opened_on = now();
        }

        $remaining = round((float) $item->remaining - $amount, 2);
        $item->remaining = max(0, $remaining);

        if ($item->remaining <= 0) {
            $item->status = InventoryStatus::Used;
        }

        $item->effective_best_before = $this->bestBefore->effective(
            $item->sealed_best_before,
            $item->opened_on,
            $item->ingredient?->use_within_after_open_days,
        );

        $item->save();

        return $item->usageLogs()->create([
            'meal_plan_entry_id' => $mealPlanEntryId,
            'amount_used' => $amount,
            'logged_at' => now(),
        ]);
    }

    /**
     * Deplete an ingredient by picking its soonest-to-expire active/frozen lot (FIFO by
     * effective best-before; nulls last). Returns null when nothing is on hand.
     */
    public function depleteIngredient(int $ingredientId, float $amount, ?int $mealPlanEntryId = null): ?UsageLog
    {
        $lot = InventoryItem::query()
            ->where('ingredient_id', $ingredientId)
            ->whereIn('status', [InventoryStatus::Active, InventoryStatus::Frozen])
            ->where('remaining', '>', 0)
            ->orderByRaw('effective_best_before is null') // non-null (soonest) first
            ->orderBy('effective_best_before')
            ->first();

        return $lot ? $this->logUsage($lot, $amount, $mealPlanEntryId) : null;
    }
}
