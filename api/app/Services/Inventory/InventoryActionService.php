<?php

namespace App\Services\Inventory;

use App\Enums\InventoryStatus;
use App\Models\InventoryItem;

/**
 * Reconciliation actions on a single inventory lot (Phase 2, ADR-0003). Freezing pauses both
 * best-before clocks by snapshotting the remaining shelf; thawing resumes it and restarts the
 * opened window. Nothing is ever auto-discarded — discard is a user action.
 */
class InventoryActionService
{
    private const FREEZE_EXTENSION_DAYS = 90;

    public function __construct(private BestBeforeCalculator $bestBefore) {}

    /** Mark a lot opened (starts the opened clock) and recompute effective best-before. */
    public function open(InventoryItem $item): InventoryItem
    {
        $item->loadMissing('ingredient');

        if (! $item->is_opened) {
            $item->is_opened = true;
            $item->opened_on = now();
        }

        $this->recomputeEffective($item);
        $item->save();

        return $item;
    }

    /** Stock-confirm: set the remaining fraction (0–1); a lot at 0 is Used. */
    public function adjustRemaining(InventoryItem $item, float $remaining): InventoryItem
    {
        $item->remaining = max(0, min(1, round($remaining, 2)));

        if ($item->remaining <= 0 && $item->status === InventoryStatus::Active) {
            $item->status = InventoryStatus::Used;
        }

        $item->save();

        return $item;
    }

    /** Freeze: pause the clock (snapshot remaining shelf), extend the displayed best-before. */
    public function freeze(InventoryItem $item): InventoryItem
    {
        if ($item->status === InventoryStatus::Frozen) {
            return $item;
        }

        $today = now();

        $item->frozen_days_remaining = $item->effective_best_before
            ? max(0, (int) ceil(($item->effective_best_before->getTimestamp() - $today->getTimestamp()) / 86400))
            : null;
        $item->frozen_on = $today;
        $item->status = InventoryStatus::Frozen;
        $item->effective_best_before = $today->copy()->addDays(self::FREEZE_EXTENSION_DAYS);

        $item->save();

        return $item;
    }

    /** Thaw: resume the paused clock; if the lot was opened, restart the opened window. */
    public function thaw(InventoryItem $item): InventoryItem
    {
        if ($item->status !== InventoryStatus::Frozen) {
            return $item;
        }

        $item->loadMissing('ingredient');
        $today = now();

        $resumedSealed = $item->frozen_days_remaining !== null
            ? $today->copy()->addDays($item->frozen_days_remaining)
            : null;

        $item->status = InventoryStatus::Active;
        $item->frozen_on = null;
        $item->frozen_days_remaining = null;

        if ($item->is_opened) {
            $item->opened_on = $today; // thaw restarts the opened clock
        }

        $item->effective_best_before = $this->bestBefore->effective(
            $resumedSealed,
            $item->is_opened ? $item->opened_on : null,
            $item->ingredient?->use_within_after_open_days,
        );

        $item->save();

        return $item;
    }

    /** Discard: user-driven only. Sets status + discard date (logged for waste patterns). */
    public function discard(InventoryItem $item): InventoryItem
    {
        $item->status = InventoryStatus::Discarded;
        $item->discarded_on = now();
        $item->save();

        return $item;
    }

    private function recomputeEffective(InventoryItem $item): void
    {
        $item->effective_best_before = $this->bestBefore->effective(
            $item->sealed_best_before,
            $item->is_opened ? $item->opened_on : null,
            $item->ingredient?->use_within_after_open_days,
        );
    }
}
