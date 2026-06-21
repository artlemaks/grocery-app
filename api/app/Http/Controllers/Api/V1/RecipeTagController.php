<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecipeTagController extends Controller
{
    public function attachTag(Request $request, Recipe $recipe): RecipeResource
    {
        $this->authorize('update', $recipe);

        $validated = $request->validate([
            'tag_id' => [
                'required',
                Rule::exists('tags', 'id')->where('household_id', $request->user()->household_id),
            ],
        ]);

        $recipe->tags()->syncWithoutDetaching([$validated['tag_id']]);

        return new RecipeResource($recipe->load('tags'));
    }

    public function detachTag(Recipe $recipe, Tag $tag): JsonResponse
    {
        $this->authorize('update', $recipe);

        $recipe->tags()->detach($tag->id);

        return response()->json(null, 204);
    }
}
