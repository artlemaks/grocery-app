<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Versioned API surface. Only health + me exist today; the real resource
| controllers (ingredients, recipes, tags, meal plans, shopping lists,
| inventory) arrive in Phase 1.
|
*/

Route::prefix('v1')->group(function () {
    Route::get('/health', \App\Http\Controllers\Api\V1\HealthController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', \App\Http\Controllers\Api\V1\MeController::class);
    });
});
