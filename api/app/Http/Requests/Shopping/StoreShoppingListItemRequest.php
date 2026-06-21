<?php

namespace App\Http\Requests\Shopping;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShoppingListItemRequest extends FormRequest
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
                'required',
                Rule::exists('ingredients', 'id')->where('household_id', $this->user()->household_id),
            ],
            'quantity' => ['nullable', 'string'],
        ];
    }
}
