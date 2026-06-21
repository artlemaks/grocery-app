<?php

namespace App\Http\Requests\MealPlan;

use App\Enums\MealPlanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreMealPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'week_start_date' => [
                'required',
                'date',
                Rule::unique('meal_plans', 'week_start_date')
                    ->where('household_id', $this->user()->household_id),
            ],
            'status' => ['nullable', new Enum(MealPlanStatus::class)],
        ];
    }
}
