<?php

namespace App\Http\Resources;

use App\Models\ShoppingListItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShoppingListItem
 */
class ShoppingListItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ingredient_id' => $this->ingredient_id,
            'quantity' => $this->quantity,
            'is_checked' => (bool) $this->is_checked,
            'source' => $this->source->value,
            'ingredient_name' => $this->whenLoaded('ingredient', fn () => $this->ingredient->name),
        ];
    }
}
