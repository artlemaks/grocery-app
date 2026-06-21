<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

class SubRecipeTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    public function test_linking_a_child_expands_into_the_parents_expanded_output(): void
    {
        $this->actingInHousehold();

        $parent = Recipe::factory()->create(['household_id' => $this->household->id]);
        $child = Recipe::factory()->create(['household_id' => $this->household->id]);

        $childIngredient = Ingredient::factory()->create(['name' => 'Garlic', 'household_id' => $this->household->id]);
        RecipeIngredient::factory()->create([
            'recipe_id' => $child->id,
            'ingredient_id' => $childIngredient->id,
            'quantity_hint' => '2 cloves',
        ]);

        $this->postJson("/api/v1/recipes/{$parent->id}/components", [
            'child_recipe_id' => $child->id,
        ])->assertCreated();

        $this->assertDatabaseHas('recipe_components', [
            'parent_recipe_id' => $parent->id,
            'child_recipe_id' => $child->id,
        ]);

        $expanded = $this->getJson("/api/v1/recipes/{$parent->id}/expanded")->assertOk();
        $expanded->assertJsonFragment([
            'ingredient_id' => $childIngredient->id,
            'name' => 'Garlic',
        ]);
    }

    public function test_cycle_is_rejected_with_422(): void
    {
        $this->actingInHousehold();

        $a = Recipe::factory()->create(['household_id' => $this->household->id]);
        $b = Recipe::factory()->create(['household_id' => $this->household->id]);

        // A -> B is fine
        $this->postJson("/api/v1/recipes/{$a->id}/components", [
            'child_recipe_id' => $b->id,
        ])->assertCreated();

        // B -> A would create a cycle
        $this->postJson("/api/v1/recipes/{$b->id}/components", [
            'child_recipe_id' => $a->id,
        ])->assertStatus(422)->assertJsonValidationErrors('child_recipe_id');
    }

    public function test_self_link_is_rejected(): void
    {
        $this->actingInHousehold();
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);

        $this->postJson("/api/v1/recipes/{$recipe->id}/components", [
            'child_recipe_id' => $recipe->id,
        ])->assertStatus(422)->assertJsonValidationErrors('child_recipe_id');
    }

    public function test_detaching_a_sub_recipe(): void
    {
        $this->actingInHousehold();
        $parent = Recipe::factory()->create(['household_id' => $this->household->id]);
        $child = Recipe::factory()->create(['household_id' => $this->household->id]);
        $parent->subRecipes()->attach($child->id);

        $this->deleteJson("/api/v1/recipes/{$parent->id}/components/{$child->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('recipe_components', [
            'parent_recipe_id' => $parent->id,
            'child_recipe_id' => $child->id,
        ]);
    }
}
