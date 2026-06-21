<?php

namespace Database\Factories;

use App\Enums\DietClass;
use App\Models\Household;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    protected $model = Ingredient::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'name' => fake()->unique()->words(2, true),
            'diet_class' => DietClass::Plant,
            'requires_open_tracking' => false,
        ];
    }

    public function meat(): static
    {
        return $this->state(fn (array $attributes) => [
            'diet_class' => DietClass::Meat,
        ]);
    }

    public function fish(): static
    {
        return $this->state(fn (array $attributes) => [
            'diet_class' => DietClass::Fish,
        ]);
    }

    public function withOpenTracking(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_open_tracking' => true,
            'use_within_after_open_days' => 5,
            'shelf_life_sealed_days' => 14,
        ]);
    }

    public function substituteOf(Ingredient $sub): static
    {
        return $this->state(fn (array $attributes) => [
            'substitute_ingredient_id' => $sub->id,
        ]);
    }
}
