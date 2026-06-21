<?php

namespace Database\Factories;

use App\Enums\InventoryLocation;
use App\Enums\InventoryStatus;
use App\Models\Household;
use App\Models\Ingredient;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'ingredient_id' => Ingredient::factory(),
            'location' => InventoryLocation::Pantry,
            'remaining' => 1.00,
            'purchased_on' => now()->toDateString(),
            'is_opened' => false,
            'status' => InventoryStatus::Active,
        ];
    }
}
