<?php

namespace App\Http\Resources;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Recipe
 */
class RecipeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'servings_default' => $this->servings_default,
            'instructions' => $this->instructions,
            'source_type' => $this->source_type?->value,
            'source_url' => $this->source_url,
            'image_url' => $this->image_url,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'ingredients' => RecipeIngredientResource::collection($this->whenLoaded('recipeIngredients')),
            'sub_recipes' => RecipeResource::collection($this->whenLoaded('subRecipes')),
        ];
    }
}
