<?php

namespace App\Http\Requests\Ingredient;

use App\Enums\DietClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreIngredientRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'diet_class' => ['required', new Enum(DietClass::class)],
            'default_unit' => ['nullable', 'string'],
            'default_pack_size' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'substitute_ingredient_id' => [
                'nullable',
                Rule::exists('ingredients', 'id')->where('household_id', $this->user()->household_id),
            ],
            'shelf_life_sealed_days' => ['nullable', 'integer', 'min:0'],
            'use_within_after_open_days' => ['nullable', 'integer', 'min:0'],
            'requires_open_tracking' => ['boolean'],
            'freezable' => ['boolean'],
        ];
    }
}
