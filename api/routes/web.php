<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\IngredientController;
use App\Http\Controllers\Web\RecipeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

    // Phase 1b-ii placeholders (keep the sidebar nav links live).
    Route::get('/planner', fn () => Inertia::render('ComingSoon', ['title' => 'Weekly Planner']));
    Route::get('/shopping', fn () => Inertia::render('ComingSoon', ['title' => 'Shopping List']));
    Route::get('/inventory', fn () => Inertia::render('ComingSoon', ['title' => 'Inventory']));
});
