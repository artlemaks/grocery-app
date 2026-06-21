<?php

namespace App\Http\Resources;

use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ingredient
 */
class IngredientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'diet_class' => $this->diet_class->value,
            'default_unit' => $this->default_unit,
            'default_pack_size' => $this->default_pack_size,
            'category' => $this->category,
            'substitute_ingredient_id' => $this->substitute_ingredient_id,
            'shelf_life_sealed_days' => $this->shelf_life_sealed_days,
            'use_within_after_open_days' => $this->use_within_after_open_days,
            'requires_open_tracking' => (bool) $this->requires_open_tracking,
        ];
    }
}
