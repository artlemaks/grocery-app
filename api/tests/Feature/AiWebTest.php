<?php

namespace Tests\Feature;

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Services\Ai\FakeLlmClient;
use App\Services\Ai\LlmClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

/**
 * Phase 3b: the Inertia web entry points for AI (import-from-URL, suggest, draft confirm) over
 * the deterministic fake — no API key.
 */
class AiWebTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'sync']);
    }

    private function fakeLlm(): FakeLlmClient
    {
        $fake = new FakeLlmClient();
        $this->app->instance(LlmClient::class, $fake);
        $this->app->instance(FakeLlmClient::class, $fake);

        return $fake;
    }

    /**
     * POST with a matching CSRF token (the container .env sets APP_ENV=local, so the framework
     * doesn't auto-skip CSRF in tests).
     */
    private function csrfPost(string $from, string $uri, array $data = []): TestResponse
    {
        return $this->withSession(['_token' => 'tok'])
            ->from($from)
            ->post($uri, array_merge($data, ['_token' => 'tok']));
    }

    public function test_import_from_url_enqueues_creates_a_draft_and_polls_complete(): void
    {
        $this->actingInHousehold();
        $this->fakeLlm();
        Http::fake(['*' => Http::response(
            '<html><head><script type="application/ld+json">'
            .json_encode(['@type' => 'Recipe', 'name' => 'Web Imported Pie', 'recipeIngredient' => ['Apple']])
            .'</script></head></html>',
            200,
        )]);

        $this->csrfPost('/recipes', '/recipes/import-url', ['url' => 'https://example.com/pie'])
            ->assertRedirect('/recipes')
            ->assertSessionHas('aiJob');

        $jobId = session('aiJob')['id'];

        $this->getJson("/ai-jobs/{$jobId}")
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $recipe = Recipe::where('name', 'Web Imported Pie')->first();
        $this->assertNotNull($recipe);
        $this->assertTrue($recipe->is_draft);
    }

    public function test_confirming_a_draft_from_the_web(): void
    {
        $this->actingInHousehold();
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id, 'is_draft' => true]);

        $this->csrfPost("/recipes/{$recipe->id}/edit", "/recipes/{$recipe->id}/confirm")
            ->assertRedirect("/recipes/{$recipe->id}/edit");

        $this->assertFalse($recipe->fresh()->is_draft);
    }

    public function test_suggest_meals_from_the_planner(): void
    {
        $this->actingInHousehold();
        $fake = $this->fakeLlm();
        $fake->push(['suggestions' => [
            ['recipe_name' => 'Granola & Yoghurt', 'reason' => 'quick breakfast', 'uses_stock' => true],
        ]]);
        $plan = MealPlan::factory()->create(['household_id' => $this->household->id]);

        $this->csrfPost('/planner', "/planner/{$plan->id}/suggest")
            ->assertRedirect('/planner')
            ->assertSessionHas('aiJob');

        $jobId = session('aiJob')['id'];

        $this->getJson("/ai-jobs/{$jobId}")
            ->assertOk()
            ->assertJsonPath('result.suggestions.0.recipe_name', 'Granola & Yoghurt');
    }
}
