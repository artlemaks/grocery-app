<?php

namespace App\Http\Requests\Inventory;

use App\Enums\InventoryLocation;
use App\Enums\InventoryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IndexInventoryItemRequest extends FormRequest
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
            'location' => ['sometimes', new Enum(InventoryLocation::class)],
            'status' => ['sometimes', new Enum(InventoryStatus::class)],
        ];
    }
}
