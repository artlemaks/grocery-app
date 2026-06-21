<?php

use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\IngredientController;
use App\Http\Controllers\Api\V1\InventoryItemController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MealPlanController;
use App\Http\Controllers\Api\V1\MealPlanEntryController;
use App\Http\Controllers\Api\V1\RecipeComponentController;
use App\Http\Controllers\Api\V1\RecipeController;
use App\Http\Controllers\Api\V1\RecipeIngredientController;
use App\Http\Controllers\Api\V1\RecipeTagController;
use App\Http\Controllers\Api\V1\ShoppingListController;
use App\Http\Controllers\Api\V1\ShoppingListItemController;
use App\Http\Controllers\Api\V1\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Larder
|--------------------------------------------------------------------------
| Phase 0: health, me. Phase 1a: the manual planning loop (ingredients,
| recipes, tags, meal plans, shopping lists, inventory, usage). Custom and
| nested routes are declared before each apiResource so literal segments
| (search, expanded, duplicate, complete) aren't swallowed by {wildcard}s.
*/

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', MeController::class);

        // Ingredients
        Route::get('ingredients/search', [IngredientController::class, 'search']);
        Route::put('ingredients/{ingredient}/substitute', [IngredientController::class, 'substitute']);
        Route::apiResource('ingredients', IngredientController::class);

        // Tags
        Route::apiResource('tags', TagController::class);

        // Recipes (+ nested ingredients, tags, sub-recipes, expansion)
        Route::get('recipes/{recipe}/expanded', [RecipeController::class, 'expanded']);
        Route::post('recipes/{recipe}/ingredients', [RecipeIngredientController::class, 'store']);
        Route::match(['put', 'patch'], 'recipes/{recipe}/ingredients/{recipeIngredient}', [RecipeIngredientController::class, 'update']);
        Route::delete('recipes/{recipe}/ingredients/{recipeIngredient}', [RecipeIngredientController::class, 'destroy']);
        Route::post('recipes/{recipe}/tags', [RecipeTagController::class, 'attachTag']);
        Route::delete('recipes/{recipe}/tags/{tag}', [RecipeTagController::class, 'detachTag']);
        Route::post('recipes/{recipe}/components', [RecipeComponentController::class, 'store']);
        Route::delete('recipes/{recipe}/components/{child}', [RecipeComponentController::class, 'destroy'])->whereNumber('child');
        Route::apiResource('recipes', RecipeController::class);

        // Meal plans (+ entries, duplicate, shopping-list generation)
        Route::post('meal-plans/{mealPlan}/duplicate', [MealPlanController::class, 'duplicate']);
        Route::post('meal-plans/{mealPlan}/shopping-list', [ShoppingListController::class, 'generate']);
        Route::post('meal-plans/{mealPlan}/entries', [MealPlanEntryController::class, 'store']);
        Route::match(['put', 'patch'], 'meal-plans/{mealPlan}/entries/{entry}', [MealPlanEntryController::class, 'update']);
        Route::delete('meal-plans/{mealPlan}/entries/{entry}', [MealPlanEntryController::class, 'destroy']);
        Route::apiResource('meal-plans', MealPlanController::class)->parameters(['meal-plans' => 'mealPlan']);

        // Shopping lists (+ items, complete)
        Route::post('shopping-lists/{shoppingList}/complete', [ShoppingListController::class, 'complete']);
        Route::post('shopping-lists/{shoppingList}/items', [ShoppingListItemController::class, 'store']);
        Route::match(['put', 'patch'], 'shopping-lists/{shoppingList}/items/{item}', [ShoppingListItemController::class, 'update']);
        Route::delete('shopping-lists/{shoppingList}/items/{item}', [ShoppingListItemController::class, 'destroy']);
        Route::get('shopping-lists', [ShoppingListController::class, 'index']);
        Route::get('shopping-lists/{shoppingList}', [ShoppingListController::class, 'show']);

        // Inventory + usage logging + reconciliation actions
        Route::get('inventory-items', [InventoryItemController::class, 'index']);
        Route::post('inventory-items/{inventoryItem}/usage', [InventoryItemController::class, 'usage']);
        Route::post('inventory-items/{inventoryItem}/open', [InventoryItemController::class, 'open']);
        Route::post('inventory-items/{inventoryItem}/adjust', [InventoryItemController::class, 'adjust']);
        Route::post('inventory-items/{inventoryItem}/freeze', [InventoryItemController::class, 'freeze']);
        Route::post('inventory-items/{inventoryItem}/thaw', [InventoryItemController::class, 'thaw']);
        Route::post('inventory-items/{inventoryItem}/discard', [InventoryItemController::class, 'discard']);
    });
});
