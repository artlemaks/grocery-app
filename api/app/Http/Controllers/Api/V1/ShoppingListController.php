<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\ShoppingListResource;
use App\Models\MealPlan;
use App\Models\ShoppingList;
use App\Services\Shopping\ShoppingListCompletionService;
use App\Services\Shopping\ShoppingListGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShoppingListController extends Controller
{
    /**
     * Generate a draft shopping list from a meal plan.
     */
    public function generate(MealPlan $mealPlan): JsonResponse
    {
        $this->authorize('create', ShoppingList::class);

        $list = app(ShoppingListGenerationService::class)->generate(
            $mealPlan->load('entries.recipe', 'household.users')
        );

        return (new ShoppingListResource($list))
            ->response()
            ->setStatusCode(201);
    }

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ShoppingList::class);

        return ShoppingListResource::collection(ShoppingList::all());
    }

    public function show(ShoppingList $shoppingList): ShoppingListResource
    {
        $this->authorize('view', $shoppingList);

        return new ShoppingListResource($shoppingList->load('items.ingredient'));
    }

    /**
     * Complete shopping: convert each checked item into an inventory lot, mark the
     * list Completed, and leave unchecked items in place to roll over.
     */
    public function complete(ShoppingList $shoppingList, ShoppingListCompletionService $completion): AnonymousResourceCollection
    {
        $this->authorize('update', $shoppingList);

        return InventoryItemResource::collection($completion->complete($shoppingList));
    }
}
