<?php

namespace App\Services\Planning;

use App\Models\Recipe;
use App\Models\User;
use App\Services\Recipes\RecipeExpansionService;
use Illuminate\Support\Collection;

/**
 * Decides whether a meal-plan entry must be cooked as two variants (meat + substitute) or
 * a single shared dish, from the household members' diets vs the recipe's ingredient
 * classes (ADR-0002). Sub-recipes are included via expansion.
 */
class MealSplitResolver
{
    public function __construct(private RecipeExpansionService $expansion) {}

    /**
     * @param  Collection<int, User>  $members
     * @return array{is_split: bool, members: list<int>}
     */
    public function resolve(Recipe $recipe, Collection $members): array
    {
        $classes = $this->expansion->expand($recipe)
            ->map(fn (array $line) => $line['ingredient']?->diet_class)
            ->filter()
            ->unique();

        // Per member: do they need a substitute for any ingredient in this dish?
        $needsSubstitute = $members->map(function (User $member) use ($classes): bool {
            $diet = $member->diet_type;

            return $diet !== null && $classes->contains(fn ($class) => $diet->excludes($class));
        });

        // Split only when members DIVERGE — some need a substitute, some don't.
        // (All-can-eat → shared original; all-need-substitute → shared substitute.)
        $isSplit = $needsSubstitute->unique()->count() > 1;

        return [
            'is_split' => $isSplit,
            'members' => $members->pluck('id')->all(),
        ];
    }
}
