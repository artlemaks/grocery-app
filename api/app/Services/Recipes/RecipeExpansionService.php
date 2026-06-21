<?php

namespace App\Services\Recipes;

use App\Exceptions\CircularRecipeException;
use App\Models\Recipe;
use Illuminate\Support\Collection;

/**
 * Flattens a recipe (and its sub-recipes, recursively) to base ingredient lines, and
 * detects sub-recipe cycles. Used by shopping-list generation and meal-split resolution.
 */
class RecipeExpansionService
{
    /**
     * Recursively expand a recipe to its base ingredient lines.
     *
     * @param  list<int>  $visited  recipe ids already on the current branch (cycle guard)
     * @return Collection<int, array{ingredient_id:int, ingredient:\App\Models\Ingredient, quantity_hint:?string, note:?string, is_optional:bool}>
     *
     * @throws CircularRecipeException
     */
    public function expand(Recipe $recipe, array $visited = []): Collection
    {
        if (in_array($recipe->id, $visited, true)) {
            throw new CircularRecipeException("Circular sub-recipe reference at recipe #{$recipe->id}.");
        }

        $visited[] = $recipe->id;
        $recipe->loadMissing('recipeIngredients.ingredient', 'subRecipes');

        $lines = $recipe->recipeIngredients->map(fn ($ri) => [
            'ingredient_id' => $ri->ingredient_id,
            'ingredient' => $ri->ingredient,
            'quantity_hint' => $ri->quantity_hint,
            'note' => $ri->note,
            'is_optional' => (bool) $ri->is_optional,
        ]);

        foreach ($recipe->subRecipes as $sub) {
            $lines = $lines->concat($this->expand($sub, $visited));
        }

        return $lines->values();
    }

    /**
     * Would linking $child as a sub-recipe of $parent create a cycle?
     * True if they are the same recipe, or $parent already appears anywhere in $child's tree.
     */
    public function wouldCreateCycle(Recipe $parent, Recipe $child): bool
    {
        if ($parent->id === $child->id) {
            return true;
        }

        return $this->treeContains($child, $parent->id);
    }

    /**
     * @param  list<int>  $visited
     */
    private function treeContains(Recipe $recipe, int $targetId, array $visited = []): bool
    {
        if ($recipe->id === $targetId) {
            return true;
        }

        if (in_array($recipe->id, $visited, true)) {
            return false;
        }

        $visited[] = $recipe->id;

        foreach ($recipe->loadMissing('subRecipes')->subRecipes as $sub) {
            if ($this->treeContains($sub, $targetId, $visited)) {
                return true;
            }
        }

        return false;
    }
}
