<?php

namespace App\Models;

use App\Enums\ShoppingItemSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Child row of a shopping list. Scoped via its parent list (no household_id of its own).
 */
class ShoppingListItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'shopping_list_id',
        'ingredient_id',
        'quantity',
        'is_checked',
        'source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_checked' => 'boolean',
            'source' => ShoppingItemSource::class,
        ];
    }

    public function shoppingList(): BelongsTo
    {
        return $this->belongsTo(ShoppingList::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
