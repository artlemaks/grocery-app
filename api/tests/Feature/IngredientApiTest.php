<?php

namespace Tests\Feature;

use App\Enums\DietClass;
use App\Models\Ingredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

class IngredientApiTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    public function test_store_creates_an_ingredient_scoped_to_the_household(): void
    {
        $this->actingInHousehold();

        $response = $this->postJson('/api/v1/ingredients', [
            'name' => 'Mince Beef',
            'diet_class' => DietClass::Meat->value,
            'requires_open_tracking' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Mince Beef')
            ->assertJsonPath('data.diet_class', 'meat')
            ->assertJsonPath('data.requires_open_tracking', false);

        $this->assertDatabaseHas('ingredients', [
            'name' => 'Mince Beef',
            'household_id' => $this->household->id,
        ]);
    }

    public function test_index_lists_only_the_current_households_ingredients(): void
    {
        $this->actingInHousehold();
        Ingredient::create(['name' => 'Tofu', 'diet_class' => DietClass::Plant]);
        Ingredient::create(['name' => 'Lentils', 'diet_class' => DietClass::Plant]);

        // Another household's ingredient (different household via factory).
        Ingredient::factory()->create(['name' => 'Foreign Steak']);

        $response = $this->getJson('/api/v1/ingredients');

        $response->assertOk()->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['Lentils', 'Tofu'], $names);
    }

    public function test_show_returns_404_for_a_foreign_households_ingredient(): void
    {
        $this->actingInHousehold();

        $foreign = Ingredient::factory()->create();

        $this->getJson("/api/v1/ingredients/{$foreign->id}")->assertNotFound();
    }

    public function test_show_returns_the_ingredient_for_the_current_household(): void
    {
        $this->actingInHousehold();
        $ingredient = Ingredient::create(['name' => 'Carrot', 'diet_class' => DietClass::Plant]);

        $this->getJson("/api/v1/ingredients/{$ingredient->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $ingredient->id)
            ->assertJsonPath('data.name', 'Carrot');
    }

    public function test_update_modifies_the_ingredient(): void
    {
        $this->actingInHousehold();
        $ingredient = Ingredient::create(['name' => 'Carrot', 'diet_class' => DietClass::Plant]);

        $this->patchJson("/api/v1/ingredients/{$ingredient->id}", [
            'name' => 'Heritage Carrot',
        ])->assertOk()->assertJsonPath('data.name', 'Heritage Carrot');

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'name' => 'Heritage Carrot',
        ]);
    }

    public function test_destroy_deletes_the_ingredient(): void
    {
        $this->actingInHousehold();
        $ingredient = Ingredient::create(['name' => 'Carrot', 'diet_class' => DietClass::Plant]);

        $this->deleteJson("/api/v1/ingredients/{$ingredient->id}")->assertNoContent();

        $this->assertDatabaseMissing('ingredients', ['id' => $ingredient->id]);
    }

    public function test_search_matches_by_name_case_insensitively(): void
    {
        $this->actingInHousehold();
        Ingredient::create(['name' => 'Cheddar Cheese', 'diet_class' => DietClass::Dairy]);
        Ingredient::create(['name' => 'Tomato', 'diet_class' => DietClass::Plant]);

        $response = $this->getJson('/api/v1/ingredients/search?q=cheese');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('Cheddar Cheese', $response->json('data.0.name'));
    }

    public function test_substitute_sets_and_clears_the_link(): void
    {
        $this->actingInHousehold();
        $beef = Ingredient::create(['name' => 'Mince Beef', 'diet_class' => DietClass::Meat]);
        $tofu = Ingredient::create(['name' => 'Tofu', 'diet_class' => DietClass::Plant]);

        // Set.
        $this->putJson("/api/v1/ingredients/{$beef->id}/substitute", [
            'substitute_ingredient_id' => $tofu->id,
        ])->assertOk()->assertJsonPath('data.substitute_ingredient_id', $tofu->id);

        $this->assertDatabaseHas('ingredients', [
            'id' => $beef->id,
            'substitute_ingredient_id' => $tofu->id,
        ]);

        // Clear.
        $this->putJson("/api/v1/ingredients/{$beef->id}/substitute", [
            'substitute_ingredient_id' => null,
        ])->assertOk()->assertJsonPath('data.substitute_ingredient_id', null);

        $this->assertDatabaseHas('ingredients', [
            'id' => $beef->id,
            'substitute_ingredient_id' => null,
        ]);
    }

    public function test_unauthenticated_index_request_is_rejected(): void
    {
        $this->getJson('/api/v1/ingredients')->assertUnauthorized();
    }
}
