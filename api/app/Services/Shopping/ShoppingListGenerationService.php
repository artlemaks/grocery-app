<?php

namespace App\Services\Shopping;

use App\Enums\InventoryStatus;
use App\Enums\ShoppingItemSource;
use App\Enums\ShoppingListStatus;
use App\Models\Ingredient;
use App\Models\InventoryItem;
use App\Models\MealPlan;
use App\Models\ShoppingList;
use App\Services\Recipes\RecipeExpansionService;
use Illuminate\Support\Collection;

/**
 * Builds a shopping list from a meal plan (spec §4.5, §5.4):
 *   expand recipes + sub-recipes → apply per-member veg substitutes → subtract on-hand
 *   and frozen inventory → de-duplicate → group by store category.
 *
 * Quantities are coarse by design (ADR-0001): an ingredient already on hand (any active
 * or frozen lot with remaining > 0) is treated as covered and dropped from the list.
 */
class ShoppingListGenerationService
{
    public function __construct(private RecipeExpansionService $expansion) {}

    public function generate(MealPlan $plan): ShoppingList
    {
        $members = $plan->household->users;

        /** @var Collection<int, Ingredient> $needed  keyed by ingredient id */
        $needed = collect();

        foreach ($plan->entries as $entry) {
            foreach ($this->expansion->expand($entry->recipe) as $line) {
                if ($line['is_optional']) {
                    continue;
                }

                foreach ($this->variantsFor($line['ingredient'], $members) as $variant) {
                    $needed->put($variant->id, $variant);
                }
            }
        }

        $list = ShoppingList::create([
            'household_id' => $plan->household_id,
            'meal_plan_id' => $plan->id,
            'status' => ShoppingListStatus::Draft,
        ]);

        $needed
            ->reject(fn (Ingredient $ingredient) => $this->haveOnHand($plan->household_id, $ingredient->id))
            ->each(fn (Ingredient $ingredient) => $list->items()->create([
                'ingredient_id' => $ingredient->id,
                'quantity' => null,
                'is_checked' => false,
                'source' => ShoppingItemSource::Plan,
            ]));

        return $list->load(['items.ingredient']);
    }

    /**
     * Which ingredient(s) to shop for one base ingredient, given who's eating:
     *  - the original, if anyone can eat it;
     *  - its substitute, if anyone excludes its class (and a substitute is defined).
     *
     * @param  Collection<int, \App\Models\User>  $members
     * @return list<Ingredient>
     */
    private function variantsFor(Ingredient $ingredient, Collection $members): array
    {
        $excluded = $members->contains(fn ($m) => $m->diet_type?->excludes($ingredient->diet_class));
        $eaten = $members->contains(fn ($m) => ! ($m->diet_type?->excludes($ingredient->diet_class) ?? false));

        $variants = [];

        if ($eaten) {
            $variants[] = $ingredient;
        }

        if ($excluded && $ingredient->substitute) {
            $variants[] = $ingredient->substitute;
        }

        return $variants;
    }

    private function haveOnHand(int $householdId, int $ingredientId): bool
    {
        return InventoryItem::query()
            ->withoutGlobalScopes()
            ->where('household_id', $householdId)
            ->where('ingredient_id', $ingredientId)
            ->whereIn('status', [InventoryStatus::Active, InventoryStatus::Frozen])
            ->where('remaining', '>', 0)
            ->exists();
    }
}
