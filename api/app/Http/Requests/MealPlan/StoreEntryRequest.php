<?php

namespace App\Http\Requests\MealPlan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntryRequest extends FormRequest
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
            'date' => ['required', 'date'],
            'slot_tag_id' => [
                'required',
                Rule::exists('tags', 'id')->where('household_id', $this->user()->household_id),
            ],
            'recipe_id' => [
                'required',
                Rule::exists('recipes', 'id')->where('household_id', $this->user()->household_id),
            ],
            'is_split' => ['nullable', 'boolean'],
        ];
    }
}
