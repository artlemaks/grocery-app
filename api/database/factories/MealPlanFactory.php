<?php

namespace Database\Factories;

use App\Enums\MealPlanStatus;
use App\Models\Household;
use App\Models\MealPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealPlan>
 */
class MealPlanFactory extends Factory
{
    protected $model = MealPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'week_start_date' => now()->startOfWeek()->toDateString(),
            'status' => MealPlanStatus::Planning,
        ];
    }
}
