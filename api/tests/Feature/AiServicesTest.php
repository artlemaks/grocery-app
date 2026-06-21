<?php

namespace Tests\Feature;

use App\Models\AiJob;
use App\Models\Household;
use App\Models\Ingredient;
use App\Models\InventoryItem;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Services\Ai\FakeLlmClient;
use App\Services\Ai\LlmClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

/**
 * Phase 3 AI capture & suggestions, exercised end-to-end with the deterministic FakeLlmClient —
 * no API key, no tokens. Queue runs sync in tests, so jobs complete within the request.
 */
class AiServicesTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    protected function setUp(): void
    {
        parent::setUp();
        // Run queued AI jobs inline so the request completes them (the container .env sets
        // QUEUE_CONNECTION=redis, which would otherwise leave jobs pending in tests).
        config(['queue.default' => 'sync']);
    }

    private function fakeLlm(): FakeLlmClient
    {
        $fake = new FakeLlmClient();
        $this->app->instance(LlmClient::class, $fake);
        $this->app->instance(FakeLlmClient::class, $fake);

        return $fake;
    }

    public function test_url_import_uses_schema_org_and_skips_the_llm(): void
    {
        $this->actingInHousehold();
        $fake = $this->fakeLlm();

        Http::fake(['*' => Http::response(
            '<html><head><script type="application/ld+json">'
            .json_encode([
                '@type' => 'Recipe',
                'name' => 'Test Bolognese',
                'recipeIngredient' => ['Mince Beef', 'Spaghetti'],
                'recipeInstructions' => [['@type' => 'HowToStep', 'text' => 'Cook it.']],
            ])
            .'</script></head><body>...</body></html>',
            200,
        )]);

        $this->postJson('/api/v1/recipes/import/url', ['url' => 'https://example.com/recipe'])
            ->assertStatus(202)
            ->assertJsonPath('status', 'completed');

        // Deterministic path: the LLM was never called.
        $this->assertCount(0, $fake->calls);

        $recipe = Recipe::where('name', 'Test Bolognese')->first();
        $this->assertNotNull($recipe);
        $this->assertTrue($recipe->is_draft);
        $this->assertSame('url', $recipe->source_type->value);
        $this->assertSame(2, $recipe->recipeIngredients()->count());
    }

    public function test_url_import_falls_back_to_the_llm_when_no_structured_data(): void
    {
        $this->actingInHousehold();
        $fake = $this->fakeLlm();
        $fake->push([
            'name' => 'LLM Extracted Stew',
            'instructions' => 'Simmer everything.',
            'ingredients' => [['name' => 'Tofu'], ['name' => 'Carrot', 'quantity_hint' => '2']],
        ]);

        Http::fake(['*' => Http::response('<html><body>just prose, no json-ld</body></html>', 200)]);

        $this->postJson('/api/v1/recipes/import/url', ['url' => 'https://example.com/stew'])
            ->assertStatus(202)
            ->assertJsonPath('status', 'completed');

        $this->assertCount(1, $fake->calls); // LLM fallback ran

        $recipe = Recipe::where('name', 'LLM Extracted Stew')->first();
        $this->assertNotNull($recipe);
        $this->assertTrue($recipe->is_draft);
        $this->assertSame(2, $recipe->recipeIngredients()->count());
        $this->assertDatabaseHas('ingredients', ['household_id' => $this->household->id, 'name' => 'Tofu']);
    }

    public function test_confirming_a_draft_recipe_passes_review(): void
    {
        $this->actingInHousehold();
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id, 'is_draft' => true]);

        $this->postJson("/api/v1/recipes/{$recipe->id}/confirm")->assertOk();

        $this->assertFalse($recipe->fresh()->is_draft);
    }

    public function test_meal_suggestions_are_grounded_in_household_data(): void
    {
        $this->actingInHousehold();
        $fake = $this->fakeLlm();
        $fake->push(['suggestions' => [
            ['recipe_name' => 'Spaghetti Bolognese', 'reason' => 'uses the mince on hand', 'uses_stock' => true],
        ]]);

        Recipe::factory()->create(['household_id' => $this->household->id, 'name' => 'Spaghetti Bolognese']);
        $ingredient = Ingredient::factory()->create(['household_id' => $this->household->id]);
        InventoryItem::factory()->create(['household_id' => $this->household->id, 'ingredient_id' => $ingredient->id]);
        $plan = MealPlan::factory()->create(['household_id' => $this->household->id]);

        $this->postJson("/api/v1/meal-plans/{$plan->id}/suggest")
            ->assertStatus(202)
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('result.suggestions.0.recipe_name', 'Spaghetti Bolognese');

        $this->assertCount(1, $fake->calls);
    }

    public function test_ai_job_of_another_household_is_not_visible(): void
    {
        $this->actingInHousehold();
        $other = Household::factory()->create();
        $foreign = AiJob::factory()->create(['household_id' => $other->id]);

        $this->getJson("/api/v1/ai-jobs/{$foreign->id}")->assertNotFound();
    }
}
