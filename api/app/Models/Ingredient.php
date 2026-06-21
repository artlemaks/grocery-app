<?php

namespace App\Models;

use App\Enums\DietClass;
use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    use BelongsToHousehold, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'name',
        'diet_class',
        'default_unit',
        'default_pack_size',
        'category',
        'substitute_ingredient_id',
        'shelf_life_sealed_days',
        'use_within_after_open_days',
        'requires_open_tracking',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diet_class' => DietClass::class,
            'requires_open_tracking' => 'boolean',
            'shelf_life_sealed_days' => 'integer',
            'use_within_after_open_days' => 'integer',
        ];
    }

    /**
     * The ingredient to use as a substitute for this one.
     */
    public function substitute(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'substitute_ingredient_id');
    }

    /**
     * Ingredients that name this ingredient as their substitute.
     */
    public function substituteFor(): HasMany
    {
        return $this->hasMany(Ingredient::class, 'substitute_ingredient_id');
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }
}
