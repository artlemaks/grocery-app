<?php

namespace App\Http\Controllers\Web;

use App\Enums\ShoppingItemSource;
use App\Enums\ShoppingListStatus;
use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Services\Shopping\ShoppingListCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ShoppingController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ShoppingList::class);

        // The active (draft/shopping) list, else the most recent one.
        $list = ShoppingList::with('items.ingredient')
            ->whereIn('status', [ShoppingListStatus::Draft, ShoppingListStatus::Shopping])
            ->latest()
            ->first()
            ?? ShoppingList::with('items.ingredient')->latest()->first();

        return Inertia::render('Shopping/Index', [
            'list' => $list ? [
                'id' => $list->id,
                'status' => $list->status->value,
            ] : null,
            'allIngredients' => \App\Models\Ingredient::orderBy('name')->get(['id', 'name']),
            // Group lines by store category for easy shopping (spec §5.4).
            'groups' => $list
                ? $list->items
                    ->groupBy(fn (ShoppingListItem $i) => $i->ingredient?->category ?: 'Other')
                    ->map(fn ($items, $category) => [
                        'category' => $category,
                        'items' => $items->map(fn (ShoppingListItem $i) => [
                            'id' => $i->id,
                            'name' => $i->ingredient?->name,
                            'quantity' => $i->quantity,
                            'is_checked' => $i->is_checked,
                            'source' => $i->source->value,
                        ])->values(),
                    ])->values()
                : [],
        ]);
    }

    public function toggleItem(Request $request, ShoppingList $shoppingList, ShoppingListItem $item): RedirectResponse
    {
        $this->authorize('update', $shoppingList);
        abort_unless($item->shopping_list_id === $shoppingList->id, 404);

        $item->update(['is_checked' => $request->boolean('is_checked')]);

        return back();
    }

    public function addItem(Request $request, ShoppingList $shoppingList): RedirectResponse
    {
        $this->authorize('update', $shoppingList);

        $data = $request->validate([
            'ingredient_id' => ['required', Rule::exists('ingredients', 'id')->where('household_id', $request->user()->household_id)],
            'quantity' => ['nullable', 'string', 'max:255'],
        ]);

        $shoppingList->items()->create([
            ...$data,
            'is_checked' => false,
            'source' => ShoppingItemSource::Manual,
        ]);

        return back()->with('success', 'Item added.');
    }

    public function complete(ShoppingList $shoppingList, ShoppingListCompletionService $completion): RedirectResponse
    {
        $this->authorize('update', $shoppingList);

        $completion->complete($shoppingList);

        return redirect('/inventory')->with('success', 'Shopping completed — items added to inventory.');
    }
}
