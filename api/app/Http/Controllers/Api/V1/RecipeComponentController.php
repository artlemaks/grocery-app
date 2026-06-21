<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Services\Recipes\RecipeExpansionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecipeComponentController extends Controller
{
    public function store(Request $request, Recipe $recipe): JsonResponse
    {
        $this->authorize('update', $recipe);

        $validated = $request->validate([
            'child_recipe_id' => [
                'required',
                Rule::exists('recipes', 'id')->where('household_id', $request->user()->household_id),
            ],
        ]);

        $child = Recipe::findOrFail($validated['child_recipe_id']);

        if (app(RecipeExpansionService::class)->wouldCreateCycle($recipe, $child)) {
            throw ValidationException::withMessages([
                'child_recipe_id' => 'Linking this recipe would create a circular sub-recipe reference.',
            ]);
        }

        $recipe->subRecipes()->syncWithoutDetaching([$child->id]);

        return (new RecipeResource($recipe->load('subRecipes')))->response()->setStatusCode(201);
    }

    public function destroy(Recipe $recipe, Recipe $child): JsonResponse
    {
        $this->authorize('update', $recipe);

        $recipe->subRecipes()->detach($child->id);

        return response()->json(null, 204);
    }
}
