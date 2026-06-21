<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', InventoryItem::class);

        $items = InventoryItem::with('ingredient')
            ->orderBy('location')
            ->orderByRaw('effective_best_before is null')
            ->orderBy('effective_best_before')
            ->get();

        return Inertia::render('Inventory/Index', [
            // Grouped by location (fridge / pantry / freezer).
            'groups' => $items
                ->groupBy(fn (InventoryItem $i) => $i->location->value)
                ->map(fn ($lots, $location) => [
                    'location' => $location,
                    'items' => $lots->map(fn (InventoryItem $i) => $this->present($i))->values(),
                ])->values(),
        ]);
    }

    private function present(InventoryItem $i): array
    {
        return [
            'id' => $i->id,
            'name' => $i->ingredient?->name,
            'remaining' => (float) $i->remaining,
            'is_opened' => $i->is_opened,
            'effective_best_before' => $i->effective_best_before?->toDateString(),
            'status' => $i->status->value,
        ];
    }
}
