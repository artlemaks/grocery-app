<?php

namespace App\Http\Resources;

use App\Models\MealPlanEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MealPlanEntry
 */
class MealPlanEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'slot_tag_id' => $this->slot_tag_id,
            'recipe_id' => $this->recipe_id,
            'is_split' => (bool) $this->is_split,
            'members' => $this->members ?? [],
        ];
    }
}
