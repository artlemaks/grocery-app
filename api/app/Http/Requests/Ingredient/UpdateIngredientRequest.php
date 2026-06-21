<?php

namespace App\Http\Requests\Ingredient;

use App\Enums\DietClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateIngredientRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string'],
            'diet_class' => ['sometimes', 'required', new Enum(DietClass::class)],
            'default_unit' => ['sometimes', 'nullable', 'string'],
            'default_pack_size' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'nullable', 'string'],
            'substitute_ingredient_id' => [
                'sometimes',
                'nullable',
                Rule::exists('ingredients', 'id')->where('household_id', $this->user()->household_id),
            ],
            'shelf_life_sealed_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'use_within_after_open_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'requires_open_tracking' => ['sometimes', 'boolean'],
        ];
    }
}
