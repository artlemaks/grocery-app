<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ShoppingItemSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shopping\StoreShoppingListItemRequest;
use App\Http\Requests\Shopping\UpdateShoppingListItemRequest;
use App\Http\Resources\ShoppingListItemResource;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Http\JsonResponse;

class ShoppingListItemController extends Controller
{
    public function store(StoreShoppingListItemRequest $request, ShoppingList $shoppingList): ShoppingListItemResource
    {
        $this->authorize('update', $shoppingList);

        $item = $shoppingList->items()->create([
            'ingredient_id' => $request->validated('ingredient_id'),
            'quantity' => $request->validated('quantity'),
            'is_checked' => false,
            'source' => ShoppingItemSource::Manual,
        ]);

        return new ShoppingListItemResource($item);
    }

    public function update(
        UpdateShoppingListItemRequest $request,
        ShoppingList $shoppingList,
        ShoppingListItem $item,
    ): ShoppingListItemResource {
        $this->authorize('update', $shoppingList);

        abort_if($item->shopping_list_id !== $shoppingList->id, 404);

        $item->update($request->validated());

        return new ShoppingListItemResource($item);
    }

    public function destroy(ShoppingList $shoppingList, ShoppingListItem $item): JsonResponse
    {
        $this->authorize('update', $shoppingList);

        abort_if($item->shopping_list_id !== $shoppingList->id, 404);

        $item->delete();

        return response()->json(status: 204);
    }
}
