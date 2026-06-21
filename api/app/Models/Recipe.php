<?php

namespace App\Models;

use App\Enums\RecipeSourceType;
use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use BelongsToHousehold, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'name',
        'servings_default',
        'instructions',
        'source_type',
        'source_url',
        'image_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_type' => RecipeSourceType::class,
            'servings_default' => 'integer',
        ];
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
            ->withPivot('quantity_hint', 'note', 'is_optional')
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'recipe_tag');
    }

    /**
     * Recipes used as components of this recipe.
     */
    public function subRecipes(): BelongsToMany
    {
        return $this->belongsToMany(
            Recipe::class,
            'recipe_components',
            'parent_recipe_id',
            'child_recipe_id',
        );
    }

    /**
     * Recipes that use this recipe as a component.
     */
    public function parentRecipes(): BelongsToMany
    {
        return $this->belongsToMany(
            Recipe::class,
            'recipe_components',
            'child_recipe_id',
            'parent_recipe_id',
        );
    }
}
