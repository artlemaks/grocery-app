<?php

namespace Database\Factories;

use App\Enums\AiJobStatus;
use App\Enums\AiJobType;
use App\Models\AiJob;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiJob>
 */
class AiJobFactory extends Factory
{
    protected $model = AiJob::class;

    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'type' => AiJobType::SuggestMeals,
            'status' => AiJobStatus::Pending,
            'input' => [],
        ];
    }
}
