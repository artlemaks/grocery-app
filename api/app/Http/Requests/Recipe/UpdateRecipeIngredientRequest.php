<?php

namespace App\Http\Requests\Recipe;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecipeIngredientRequest extends FormRequest
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
            'ingredient_id' => [
                'sometimes',
                'required',
                Rule::exists('ingredients', 'id')->where('household_id', $this->user()->household_id),
            ],
            'quantity_hint' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'is_optional' => ['boolean'],
        ];
    }
}
