<?php

namespace Tests\Feature;

use App\Enums\DietType;
use App\Models\Ingredient;
use App\Models\InventoryItem;
use App\Models\MealPlan;
use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

class ShoppingListGenerationTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    public function test_generation_includes_a_meat_ingredient_and_its_substitute_for_a_mixed_household(): void
    {
        $this->actingInHousehold(DietType::Omnivore);
        $this->addMember(DietType::Pescatarian);

        $substitute = Ingredient::factory()->fish()->create([
            'household_id' => $this->household->id,
            'name' => 'Salmon',
        ]);
        $meat = Ingredient::factory()->meat()->create([
            'household_id' => $this->household->id,
            'name' => 'Beef Mince',
            'substitute_ingredient_id' => $substitute->id,
        ]);

        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);
        RecipeIngredient::factory()->create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => $meat->id,
        ]);

        $plan = MealPlan::factory()->create(['household_id' => $this->household->id]);
        MealPlanEntry::factory()->create([
            'meal_plan_id' => $plan->id,
            'recipe_id' => $recipe->id,
        ]);

        $response = $this->postJson("/api/v1/meal-plans/{$plan->id}/shopping-list");

        $response->assertStatus(201);

        $ingredientIds = collect($response->json('data.items'))->pluck('ingredient_id');
        $this->assertTrue($ingredientIds->contains($meat->id), 'meat ingredient present');
        $this->assertTrue($ingredientIds->contains($substitute->id), 'substitute ingredient present');
    }

    public function test_an_ingredient_already_in_inventory_is_not_added(): void
    {
        $this->actingInHousehold(DietType::Omnivore);

        $onHand = Ingredient::factory()->create([
            'household_id' => $this->household->id,
            'name' => 'Pasta',
        ]);

        InventoryItem::factory()->create([
            'household_id' => $this->household->id,
            'ingredient_id' => $onHand->id,
            'remaining' => 1.00,
        ]);

        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);
        RecipeIngredient::factory()->create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => $onHand->id,
        ]);

        $plan = MealPlan::factory()->create(['household_id' => $this->household->id]);
        MealPlanEntry::factory()->create([
            'meal_plan_id' => $plan->id,
            'recipe_id' => $recipe->id,
        ]);

        $response = $this->postJson("/api/v1/meal-plans/{$plan->id}/shopping-list");

        $response->assertStatus(201);

        $ingredientIds = collect($response->json('data.items'))->pluck('ingredient_id');
        $this->assertFalse($ingredientIds->contains($onHand->id), 'on-hand ingredient excluded');
    }
}
