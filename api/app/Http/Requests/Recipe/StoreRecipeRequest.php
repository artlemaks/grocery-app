<?php

namespace App\Http\Requests\Recipe;

use App\Enums\RecipeSourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreRecipeRequest extends FormRequest
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
            'servings_default' => ['nullable', 'integer', 'min:1'],
            'instructions' => ['nullable', 'string'],
            'source_type' => ['nullable', new Enum(RecipeSourceType::class)],
            'source_url' => ['nullable', 'url'],
            'image_url' => ['nullable', 'url'],
        ];
    }
}
