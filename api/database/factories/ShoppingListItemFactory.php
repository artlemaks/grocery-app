<?php

namespace Database\Factories;

use App\Enums\ShoppingItemSource;
use App\Models\Ingredient;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShoppingListItem>
 */
class ShoppingListItemFactory extends Factory
{
    protected $model = ShoppingListItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shopping_list_id' => ShoppingList::factory(),
            'ingredient_id' => Ingredient::factory(),
            'quantity' => null,
            'is_checked' => false,
            'source' => ShoppingItemSource::Plan,
        ];
    }
}
