<?php

namespace Database\Factories;

use App\Enums\ShoppingListStatus;
use App\Models\Household;
use App\Models\ShoppingList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShoppingList>
 */
class ShoppingListFactory extends Factory
{
    protected $model = ShoppingList::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'meal_plan_id' => null,
            'status' => ShoppingListStatus::Draft,
        ];
    }
}
