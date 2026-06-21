<?php

namespace App\Services\Inventory;

use Illuminate\Support\Carbon;

/**
 * The two-clock best-before model (ADR-0003). Pure date logic — no persistence.
 */
class BestBeforeCalculator
{
    /**
     * Sealed best-before = purchase date + the ingredient's sealed shelf life.
     */
    public function sealedFrom(?Carbon $purchasedOn, ?int $shelfLifeSealedDays): ?Carbon
    {
        if (! $purchasedOn || $shelfLifeSealedDays === null) {
            return null;
        }

        return $purchasedOn->copy()->addDays($shelfLifeSealedDays);
    }

    /**
     * Effective best-before = the EARLIER of the sealed clock and the opened clock
     * (opened_on + use-within-after-open). Null when neither clock is known.
     */
    public function effective(?Carbon $sealedBestBefore, ?Carbon $openedOn, ?int $useWithinAfterOpenDays): ?Carbon
    {
        $openedClock = ($openedOn && $useWithinAfterOpenDays !== null)
            ? $openedOn->copy()->addDays($useWithinAfterOpenDays)
            : null;

        $candidates = array_values(array_filter([$sealedBestBefore, $openedClock]));

        if ($candidates === []) {
            return null;
        }

        return collect($candidates)->sort()->first();
    }
}
