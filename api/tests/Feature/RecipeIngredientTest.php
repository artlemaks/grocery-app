<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

class RecipeIngredientTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    public function test_adds_an_ingredient_line_to_a_recipe(): void
    {
        $this->actingInHousehold();
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);
        $ingredient = Ingredient::factory()->create(['household_id' => $this->household->id]);

        $response = $this->postJson("/api/v1/recipes/{$recipe->id}/ingredients", [
            'ingredient_id' => $ingredient->id,
            'quantity_hint' => '200g',
            'is_optional' => false,
        ]);

        $response->assertCreated()->assertJsonPath('data.id', $recipe->id);

        $this->assertDatabaseHas('recipe_ingredients', [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ingredient->id,
            'quantity_hint' => '200g',
        ]);
    }

    public function test_rejects_ingredient_from_another_household(): void
    {
        $this->actingInHousehold();
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);

        // create an ingredient that genuinely belongs to a different household
        $foreignHousehold = \App\Models\Household::factory()->create();
        $foreignIngredient = Ingredient::factory()->create(['household_id' => $foreignHousehold->id]);

        $this->postJson("/api/v1/recipes/{$recipe->id}/ingredients", [
            'ingredient_id' => $foreignIngredient->id,
        ])->assertStatus(422)->assertJsonValidationErrors('ingredient_id');
    }

    public function test_removes_an_ingredient_line(): void
    {
        $this->actingInHousehold();
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);
        $line = RecipeIngredient::factory()->create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => Ingredient::factory()->create(['household_id' => $this->household->id])->id,
        ]);

        $this->deleteJson("/api/v1/recipes/{$recipe->id}/ingredients/{$line->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('recipe_ingredients', ['id' => $line->id]);
    }

    public function test_cannot_remove_line_belonging_to_a_different_recipe(): void
    {
        $this->actingInHousehold();
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);
        $otherRecipe = Recipe::factory()->create(['household_id' => $this->household->id]);
        $line = RecipeIngredient::factory()->create([
            'recipe_id' => $otherRecipe->id,
            'ingredient_id' => Ingredient::factory()->create(['household_id' => $this->household->id])->id,
        ]);

        $this->deleteJson("/api/v1/recipes/{$recipe->id}/ingredients/{$line->id}")
            ->assertNotFound();
    }
}
