<?php

namespace App\Http\Controllers\Web;

use App\Enums\InventoryStatus;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Services\Inventory\InventoryActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function open(InventoryItem $inventoryItem, InventoryActionService $actions): RedirectResponse
    {
        $this->authorize('update', $inventoryItem);
        $actions->open($inventoryItem);

        return back();
    }

    public function adjust(Request $request, InventoryItem $inventoryItem, InventoryActionService $actions): RedirectResponse
    {
        $this->authorize('update', $inventoryItem);
        $data = $request->validate(['remaining' => ['required', 'numeric', 'between:0,1']]);
        $actions->adjustRemaining($inventoryItem, (float) $data['remaining']);

        return back()->with('success', 'Stock updated.');
    }

    public function freeze(InventoryItem $inventoryItem, InventoryActionService $actions): RedirectResponse
    {
        $this->authorize('update', $inventoryItem);
        $actions->freeze($inventoryItem);

        return back()->with('success', 'Frozen.');
    }

    public function thaw(InventoryItem $inventoryItem, InventoryActionService $actions): RedirectResponse
    {
        $this->authorize('update', $inventoryItem);
        $actions->thaw($inventoryItem);

        return back()->with('success', 'Thawed.');
    }

    public function discard(InventoryItem $inventoryItem, InventoryActionService $actions): RedirectResponse
    {
        $this->authorize('update', $inventoryItem);
        $actions->discard($inventoryItem);

        return back()->with('success', 'Discarded.');
    }

    public function index(): Response
    {
        $this->authorize('viewAny', InventoryItem::class);

        $items = InventoryItem::with('ingredient')
            ->whereIn('status', [InventoryStatus::Active, InventoryStatus::Frozen])
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
