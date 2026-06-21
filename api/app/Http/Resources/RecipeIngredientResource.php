<?php

namespace App\Http\Resources;

use App\Models\RecipeIngredient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RecipeIngredient
 */
class RecipeIngredientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recipe_id' => $this->recipe_id,
            'ingredient_id' => $this->ingredient_id,
            'quantity_hint' => $this->quantity_hint,
            'note' => $this->note,
            'is_optional' => (bool) $this->is_optional,
            'ingredient' => new IngredientResource($this->whenLoaded('ingredient')),
        ];
    }
}
