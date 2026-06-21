<?php

namespace App\Http\Controllers\Web;

use App\Enums\InventoryStatus;
use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\InventoryItem;
use App\Models\Recipe;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // All counts are household-scoped by the BelongsToHousehold global scope.
        return Inertia::render('Dashboard', [
            'stats' => [
                'recipes' => Recipe::count(),
                'ingredients' => Ingredient::count(),
                'inventory' => InventoryItem::where('status', InventoryStatus::Active)->count(),
            ],
        ]);
    }
}
