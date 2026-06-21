<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

class RecipeExpansionTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    public function test_expanded_returns_union_of_base_and_sub_recipe_ingredients(): void
    {
        $this->actingInHousehold();

        $parent = Recipe::factory()->create(['household_id' => $this->household->id]);
        $sub = Recipe::factory()->create(['household_id' => $this->household->id]);

        $baseIngredient = Ingredient::factory()->create(['name' => 'Pasta', 'household_id' => $this->household->id]);
        $subIngredient = Ingredient::factory()->create(['name' => 'Tomato', 'household_id' => $this->household->id]);

        RecipeIngredient::factory()->create([
            'recipe_id' => $parent->id,
            'ingredient_id' => $baseIngredient->id,
            'quantity_hint' => '500g',
        ]);
        RecipeIngredient::factory()->create([
            'recipe_id' => $sub->id,
            'ingredient_id' => $subIngredient->id,
            'quantity_hint' => '3',
        ]);

        $parent->subRecipes()->attach($sub->id);

        $response = $this->getJson("/api/v1/recipes/{$parent->id}/expanded")->assertOk();

        $response->assertJsonCount(2, 'data');
        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertContains('Pasta', $names);
        $this->assertContains('Tomato', $names);
    }

    public function test_expanded_returns_only_base_ingredients_when_no_sub_recipes(): void
    {
        $this->actingInHousehold();

        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);
        $ingredient = Ingredient::factory()->create(['name' => 'Rice', 'household_id' => $this->household->id]);
        RecipeIngredient::factory()->create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ingredient->id,
        ]);

        $this->getJson("/api/v1/recipes/{$recipe->id}/expanded")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Rice');
    }
}
