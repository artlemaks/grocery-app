<?php

namespace Database\Factories;

use App\Models\MealPlan;
use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealPlanEntry>
 */
class MealPlanEntryFactory extends Factory
{
    protected $model = MealPlanEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meal_plan_id' => MealPlan::factory(),
            'date' => now()->toDateString(),
            'slot_tag_id' => Tag::factory(),
            'recipe_id' => Recipe::factory(),
            'is_split' => false,
            'members' => [],
        ];
    }
}
