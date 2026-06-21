<?php

namespace App\Http\Controllers\Web;

use App\Enums\InventoryStatus;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Services\Inventory\InventoryDepletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CookController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', InventoryItem::class);

        $items = InventoryItem::with('ingredient')
            ->whereIn('status', [InventoryStatus::Active, InventoryStatus::Frozen])
            ->where('remaining', '>', 0)
            ->orderByRaw('effective_best_before is null')
            ->orderBy('effective_best_before')
            ->get();

        return Inertia::render('Cook/Index', [
            'items' => $items->map(fn (InventoryItem $i) => [
                'id' => $i->id,
                'name' => $i->ingredient?->name,
                'remaining' => (float) $i->remaining,
                'location' => $i->location->value,
                'is_opened' => $i->is_opened,
            ])->values(),
        ]);
    }

    public function logUsage(Request $request, InventoryItem $inventoryItem, InventoryDepletionService $depletion): RedirectResponse
    {
        $this->authorize('update', $inventoryItem);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'between:0.01,1'],
        ]);

        $depletion->logUsage($inventoryItem, (float) $data['amount']);

        return back()->with('success', 'Usage logged.');
    }
}
