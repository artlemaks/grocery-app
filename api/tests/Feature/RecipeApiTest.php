<?php

namespace Tests\Feature;

use App\Enums\RecipeSourceType;
use App\Models\Household;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

class RecipeApiTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    public function test_lists_only_recipes_in_the_household(): void
    {
        $this->actingInHousehold();
        Recipe::factory()->create(['name' => 'Mine', 'household_id' => $this->household->id]);

        $other = Household::factory()->create();
        Recipe::factory()->create(['household_id' => $other->id, 'name' => 'Theirs']);

        $response = $this->getJson('/api/v1/recipes');

        $response->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Mine');
    }

    public function test_creates_a_recipe_scoped_to_the_household(): void
    {
        $this->actingInHousehold();

        $response = $this->postJson('/api/v1/recipes', [
            'name' => 'Bolognese',
            'servings_default' => 4,
            'instructions' => 'Cook it.',
            'source_url' => 'https://example.com/recipe',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Bolognese')
            ->assertJsonPath('data.servings_default', 4)
            ->assertJsonPath('data.source_type', RecipeSourceType::Manual->value);

        $this->assertDatabaseHas('recipes', [
            'name' => 'Bolognese',
            'household_id' => $this->household->id,
        ]);
    }

    public function test_validates_required_name(): void
    {
        $this->actingInHousehold();

        $this->postJson('/api/v1/recipes', ['servings_default' => 2])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_shows_a_recipe_with_relations(): void
    {
        $this->actingInHousehold();
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);

        $this->getJson("/api/v1/recipes/{$recipe->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $recipe->id)
            ->assertJsonStructure(['data' => ['id', 'name', 'ingredients', 'tags', 'sub_recipes']]);
    }

    public function test_updates_a_recipe(): void
    {
        $this->actingInHousehold();
        $recipe = Recipe::factory()->create(['name' => 'Old', 'household_id' => $this->household->id]);

        $this->putJson("/api/v1/recipes/{$recipe->id}", ['name' => 'New'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New');

        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'name' => 'New']);
    }

    public function test_deletes_a_recipe(): void
    {
        $this->actingInHousehold();
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);

        $this->deleteJson("/api/v1/recipes/{$recipe->id}")->assertNoContent();

        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
    }

    public function test_cannot_access_recipe_from_another_household(): void
    {
        $this->actingInHousehold();

        $other = Household::factory()->create();
        User::factory()->create(['household_id' => $other->id]);
        $foreign = Recipe::factory()->create(['household_id' => $other->id]);

        $this->getJson("/api/v1/recipes/{$foreign->id}")->assertNotFound();
        $this->putJson("/api/v1/recipes/{$foreign->id}", ['name' => 'x'])->assertNotFound();
        $this->deleteJson("/api/v1/recipes/{$foreign->id}")->assertNotFound();
    }
}
