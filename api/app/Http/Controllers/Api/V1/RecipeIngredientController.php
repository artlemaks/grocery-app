<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recipe\StoreRecipeIngredientRequest;
use App\Http\Requests\Recipe\UpdateRecipeIngredientRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Http\JsonResponse;

class RecipeIngredientController extends Controller
{
    public function store(StoreRecipeIngredientRequest $request, Recipe $recipe): JsonResponse
    {
        $this->authorize('update', $recipe);

        $recipe->recipeIngredients()->create($request->validated());

        return (new RecipeResource(
            $recipe->load(['recipeIngredients.ingredient', 'tags', 'subRecipes'])
        ))->response()->setStatusCode(201);
    }

    public function update(UpdateRecipeIngredientRequest $request, Recipe $recipe, RecipeIngredient $recipeIngredient): RecipeResource
    {
        $this->authorize('update', $recipe);

        abort_unless($recipeIngredient->recipe_id === $recipe->id, 404);

        $recipeIngredient->update($request->validated());

        return new RecipeResource(
            $recipe->load(['recipeIngredients.ingredient', 'tags', 'subRecipes'])
        );
    }

    public function destroy(Recipe $recipe, RecipeIngredient $recipeIngredient): JsonResponse
    {
        $this->authorize('update', $recipe);

        abort_unless($recipeIngredient->recipe_id === $recipe->id, 404);

        $recipeIngredient->delete();

        return response()->json(null, 204);
    }
}
