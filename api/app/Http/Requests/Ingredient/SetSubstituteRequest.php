<?php

namespace App\Http\Requests\Ingredient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetSubstituteRequest extends FormRequest
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
            'substitute_ingredient_id' => [
                'nullable',
                Rule::exists('ingredients', 'id')->where('household_id', $this->user()->household_id),
            ],
        ];
    }
}
