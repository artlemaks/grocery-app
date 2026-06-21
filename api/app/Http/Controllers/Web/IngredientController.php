<?php

namespace App\Http\Controllers\Web;

use App\Enums\DietClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingredient\SetSubstituteRequest;
use App\Http\Requests\Ingredient\StoreIngredientRequest;
use App\Http\Requests\Ingredient\UpdateIngredientRequest;
use App\Http\Resources\IngredientResource;
use App\Models\Ingredient;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inertia web controller for the Ingredient Library. Reuses the same FormRequests and
 * Resource as the JSON API (App\Http\Controllers\Api\V1\IngredientController) — only the
 * transport differs (Inertia render / redirect vs JSON).
 */
class IngredientController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Ingredient::class);

        $ingredients = Ingredient::with('substitute')->orderBy('name')->get();

        return Inertia::render('Ingredients/Index', [
            'ingredients' => IngredientResource::collection($ingredients)->resolve(),
            'dietClasses' => array_map(fn (DietClass $c) => $c->value, DietClass::cases()),
        ]);
    }

    public function store(StoreIngredientRequest $request): RedirectResponse
    {
        $this->authorize('create', Ingredient::class);

        Ingredient::create($request->validated());

        return back()->with('success', 'Ingredient added.');
    }

    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): RedirectResponse
    {
        $this->authorize('update', $ingredient);

        $ingredient->update($request->validated());

        return back()->with('success', 'Ingredient updated.');
    }

    public function substitute(SetSubstituteRequest $request, Ingredient $ingredient): RedirectResponse
    {
        $this->authorize('update', $ingredient);

        $ingredient->update(['substitute_ingredient_id' => $request->validated('substitute_ingredient_id')]);

        return back()->with('success', 'Substitute updated.');
    }

    public function destroy(Ingredient $ingredient): RedirectResponse
    {
        $this->authorize('delete', $ingredient);

        $ingredient->delete();

        return back()->with('success', 'Ingredient removed.');
    }
}
