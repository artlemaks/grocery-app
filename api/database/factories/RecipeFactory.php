<?php

namespace Database\Factories;

use App\Enums\RecipeSourceType;
use App\Models\Household;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    protected $model = Recipe::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'name' => fake()->words(3, true),
            'servings_default' => 2,
            'source_type' => RecipeSourceType::Manual,
        ];
    }
}
