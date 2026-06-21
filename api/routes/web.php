<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\CookController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\IngredientController;
use App\Http\Controllers\Web\InventoryController;
use App\Http\Controllers\Web\PlannerController;
use App\Http\Controllers\Web\RecipeController;
use App\Http\Controllers\Web\ShoppingController;
use Illuminate\Support\Facades\Route;

/*
| Larder web (Inertia + Vue). Phase 1b-i: auth, dashboard, ingredients, recipes.
| Planner / shopping / inventory screens land in Phase 1b-ii.
| Web controllers reuse the same services/models as the /api/v1 JSON surface.
*/

Route::get('/', fn () => redirect('/dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Ingredient library
    Route::get('/ingredients', [IngredientController::class, 'index']);
    Route::post('/ingredients', [IngredientController::class, 'store']);
    Route::put('/ingredients/{ingredient}', [IngredientController::class, 'update']);
    Route::put('/ingredients/{ingredient}/substitute', [IngredientController::class, 'substitute']);
    Route::delete('/ingredients/{ingredient}', [IngredientController::class, 'destroy']);

    // Recipes + editor
    Route::get('/recipes', [RecipeController::class, 'index']);
    Route::post('/recipes', [RecipeController::class, 'store']);
    Route::get('/recipes/{recipe}/edit', [RecipeController::class, 'edit']);
    Route::put('/recipes/{recipe}', [RecipeController::class, 'update']);
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy']);
    Route::post('/recipes/{recipe}/ingredients', [RecipeController::class, 'addIngredient']);
    Route::delete('/recipes/{recipe}/ingredients/{recipeIngredient}', [RecipeController::class, 'removeIngredient']);
    Route::post('/recipes/{recipe}/tags', [RecipeController::class, 'attachTag']);
    Route::delete('/recipes/{recipe}/tags/{tag}', [RecipeController::class, 'detachTag']);
    Route::post('/recipes/{recipe}/components', [RecipeController::class, 'addComponent']);
    Route::delete('/recipes/{recipe}/components/{child}', [RecipeController::class, 'removeComponent'])->whereNumber('child');

    // Weekly planner
    Route::get('/planner', [PlannerController::class, 'index']);
    Route::post('/planner/{mealPlan}/entries', [PlannerController::class, 'storeEntry']);
    Route::delete('/planner/{mealPlan}/entries/{entry}', [PlannerController::class, 'destroyEntry']);
    Route::post('/planner/{mealPlan}/generate', [PlannerController::class, 'generate']);

    // Shopping list
    Route::get('/shopping', [ShoppingController::class, 'index']);
    Route::post('/shopping/{shoppingList}/items', [ShoppingController::class, 'addItem']);
    Route::put('/shopping/{shoppingList}/items/{item}', [ShoppingController::class, 'toggleItem']);
    Route::post('/shopping/{shoppingList}/complete', [ShoppingController::class, 'complete']);

    // Inventory + cook (usage logging)
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::get('/cook', [CookController::class, 'index']);
    Route::post('/cook/{inventoryItem}/usage', [CookController::class, 'logUsage']);
});
