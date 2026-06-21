<?php

namespace App\Http\Resources;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryItem
 */
class InventoryItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ingredient_id' => $this->ingredient_id,
            'location' => $this->location->value,
            'remaining' => (float) $this->remaining,
            'purchased_on' => $this->purchased_on?->toDateString(),
            'is_opened' => (bool) $this->is_opened,
            'opened_on' => $this->opened_on?->toDateString(),
            'sealed_best_before' => $this->sealed_best_before?->toDateString(),
            'effective_best_before' => $this->effective_best_before?->toDateString(),
            'status' => $this->status->value,
            'ingredient_name' => $this->whenLoaded('ingredient', fn () => $this->ingredient->name),
        ];
    }
}
