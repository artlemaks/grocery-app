<?php

namespace App\Http\Controllers\Web;

use App\Enums\InventoryLocation;
use App\Enums\InventoryStatus;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The weekly reconciliation flow (spec §5.7): confirm stock, surface what's expiring, suggest
 * freezing (rules-based), and surface discard candidates. Nothing is auto-discarded — the user
 * acts via the shared inventory action routes (open/adjust/freeze/thaw/discard).
 */
class ReconcileController extends Controller
{
    private const EXPIRING_WINDOW_DAYS = 4;

    public function index(): Response
    {
        $this->authorize('viewAny', InventoryItem::class);

        $today = Carbon::now()->startOfDay();
        $soon = $today->copy()->addDays(self::EXPIRING_WINDOW_DAYS)->toDateString();
        $todayStr = $today->toDateString();

        $lots = InventoryItem::with('ingredient')
            ->whereIn('status', [InventoryStatus::Active, InventoryStatus::Frozen])
            ->where('remaining', '>', 0)
            ->orderByRaw('effective_best_before is null')
            ->orderBy('effective_best_before')
            ->get();

        $isActive = fn (InventoryItem $i) => $i->status === InventoryStatus::Active;
        $expiringBy = fn (InventoryItem $i, string $date, bool $strict = false) => $i->effective_best_before
            && ($strict
                ? $i->effective_best_before->toDateString() < $date
                : $i->effective_best_before->toDateString() <= $date);

        return Inertia::render('Reconcile/Index', [
            'stock' => $lots->map(fn ($i) => $this->present($i))->values(),
            'expiring' => $lots->filter(fn ($i) => $isActive($i) && $expiringBy($i, $soon))
                ->map(fn ($i) => $this->present($i))->values(),
            'freezeSuggestions' => $lots->filter(fn ($i) => $isActive($i)
                && $i->location !== InventoryLocation::Freezer
                && ($i->ingredient?->freezable ?? true)
                && $expiringBy($i, $soon))
                ->map(fn ($i) => $this->present($i))->values(),
            'discardCandidates' => $lots->filter(fn ($i) => $isActive($i) && $expiringBy($i, $todayStr, strict: true))
                ->map(fn ($i) => $this->present($i))->values(),
        ]);
    }

    private function present(InventoryItem $i): array
    {
        return [
            'id' => $i->id,
            'name' => $i->ingredient?->name,
            'location' => $i->location->value,
            'remaining' => (float) $i->remaining,
            'is_opened' => $i->is_opened,
            'status' => $i->status->value,
            'effective_best_before' => $i->effective_best_before?->toDateString(),
        ];
    }
}
