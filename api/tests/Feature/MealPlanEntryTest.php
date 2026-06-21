<?php

namespace Tests\Feature;

use App\Enums\DietType;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

class MealPlanEntryTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    private function recipeWithIngredient(Ingredient $ingredient): Recipe
    {
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);
        $recipe->recipeIngredients()->create(['ingredient_id' => $ingredient->id]);

        return $recipe;
    }

    public function test_creates_an_entry_on_the_plan(): void
    {
        $this->actingInHousehold();
        $plan = MealPlan::factory()->create(['household_id' => $this->household->id]);
        $tag = Tag::factory()->create(['household_id' => $this->household->id]);
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);

        $response = $this->postJson("/api/v1/meal-plans/{$plan->id}/entries", [
            'date' => '2026-06-21',
            'slot_tag_id' => $tag->id,
            'recipe_id' => $recipe->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.recipe_id', $recipe->id);
        $response->assertJsonPath('data.slot_tag_id', $tag->id);
        $response->assertJsonPath('data.date', '2026-06-21');

        $this->assertDatabaseHas('meal_plan_entries', [
            'meal_plan_id' => $plan->id,
            'recipe_id' => $recipe->id,
        ]);
    }

    public function test_meat_recipe_splits_for_omnivore_plus_pescatarian(): void
    {
        $this->actingInHousehold(DietType::Omnivore);
        $this->addMember(DietType::Pescatarian);

        $plan = MealPlan::factory()->create(['household_id' => $this->household->id]);
        $tag = Tag::factory()->create(['household_id' => $this->household->id]);

        $meat = Ingredient::factory()->meat()->create(['household_id' => $this->household->id]);
        $recipe = $this->recipeWithIngredient($meat);

        $response = $this->postJson("/api/v1/meal-plans/{$plan->id}/entries", [
            'date' => '2026-06-21',
            'slot_tag_id' => $tag->id,
            'recipe_id' => $recipe->id,
        ]);

        $response->assertCreated();
        // Omnivore eats meat, pescatarian needs a substitute -> diverge -> split.
        $response->assertJsonPath('data.is_split', true);
    }

    public function test_fish_recipe_is_shared_for_omnivore_plus_pescatarian(): void
    {
        $this->actingInHousehold(DietType::Omnivore);
        $this->addMember(DietType::Pescatarian);

        $plan = MealPlan::factory()->create(['household_id' => $this->household->id]);
        $tag = Tag::factory()->create(['household_id' => $this->household->id]);

        $fish = Ingredient::factory()->fish()->create(['household_id' => $this->household->id]);
        $recipe = $this->recipeWithIngredient($fish);

        $response = $this->postJson("/api/v1/meal-plans/{$plan->id}/entries", [
            'date' => '2026-06-21',
            'slot_tag_id' => $tag->id,
            'recipe_id' => $recipe->id,
        ]);

        $response->assertCreated();
        // Both can eat fish -> no divergence -> shared.
        $response->assertJsonPath('data.is_split', false);
    }

    public function test_is_split_override_is_respected(): void
    {
        $this->actingInHousehold(DietType::Omnivore);
        $this->addMember(DietType::Pescatarian);

        $plan = MealPlan::factory()->create(['household_id' => $this->household->id]);
        $tag = Tag::factory()->create(['household_id' => $this->household->id]);

        // Fish would resolve to NOT split; force it split via the override.
        $fish = Ingredient::factory()->fish()->create(['household_id' => $this->household->id]);
        $recipe = $this->recipeWithIngredient($fish);

        $response = $this->postJson("/api/v1/meal-plans/{$plan->id}/entries", [
            'date' => '2026-06-21',
            'slot_tag_id' => $tag->id,
            'recipe_id' => $recipe->id,
            'is_split' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.is_split', true);
    }

    public function test_destroy_removes_the_entry(): void
    {
        $this->actingInHousehold();
        $plan = MealPlan::factory()->create(['household_id' => $this->household->id]);
        $tag = Tag::factory()->create(['household_id' => $this->household->id]);
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);

        $entry = $plan->entries()->create([
            'date' => '2026-06-21',
            'slot_tag_id' => $tag->id,
            'recipe_id' => $recipe->id,
            'is_split' => false,
            'members' => [],
        ]);

        $this->deleteJson("/api/v1/meal-plans/{$plan->id}/entries/{$entry->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('meal_plan_entries', ['id' => $entry->id]);
    }

    public function test_destroy_404s_when_entry_belongs_to_another_plan(): void
    {
        $this->actingInHousehold();
        $planA = MealPlan::factory()->create(['household_id' => $this->household->id]);
        $planB = MealPlan::factory()->create([
            'household_id' => $this->household->id,
            'week_start_date' => '2026-07-01',
        ]);
        $tag = Tag::factory()->create(['household_id' => $this->household->id]);
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);

        $entry = $planB->entries()->create([
            'date' => '2026-07-01',
            'slot_tag_id' => $tag->id,
            'recipe_id' => $recipe->id,
            'is_split' => false,
            'members' => [],
        ]);

        $this->deleteJson("/api/v1/meal-plans/{$planA->id}/entries/{$entry->id}")
            ->assertNotFound();
    }
}
