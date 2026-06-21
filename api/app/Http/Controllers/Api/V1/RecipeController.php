<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RecipeSourceType;
use App\Exceptions\CircularRecipeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recipe\StoreRecipeRequest;
use App\Http\Requests\Recipe\UpdateRecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Services\Recipes\RecipeExpansionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecipeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Recipe::class);

        return RecipeResource::collection(
            Recipe::query()->latest()->get()
        );
    }

    public function store(StoreRecipeRequest $request): RecipeResource
    {
        $this->authorize('create', Recipe::class);

        $data = $request->validated();
        $data['source_type'] ??= RecipeSourceType::Manual;

        $recipe = Recipe::create($data);

        return new RecipeResource($recipe);
    }

    public function show(Recipe $recipe): RecipeResource
    {
        $this->authorize('view', $recipe);

        $recipe->load(['recipeIngredients.ingredient', 'tags', 'subRecipes']);

        return new RecipeResource($recipe);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RecipeResource
    {
        $this->authorize('update', $recipe);

        $recipe->update($request->validated());

        return new RecipeResource($recipe);
    }

    public function destroy(Recipe $recipe): JsonResponse
    {
        $this->authorize('delete', $recipe);

        $recipe->delete();

        return response()->json(null, 204);
    }

    /**
     * Flatten a recipe (including its sub-recipes) to base ingredient lines.
     */
    public function expanded(Recipe $recipe): JsonResponse
    {
        $this->authorize('view', $recipe);

        try {
            $items = app(RecipeExpansionService::class)->expand($recipe);
        } catch (CircularRecipeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $items->map(fn (array $item) => [
                'ingredient_id' => $item['ingredient_id'],
                'name' => $item['ingredient']->name,
                'quantity_hint' => $item['quantity_hint'],
                'note' => $item['note'],
                'is_optional' => $item['is_optional'],
            ])->values(),
        ]);
    }
}
