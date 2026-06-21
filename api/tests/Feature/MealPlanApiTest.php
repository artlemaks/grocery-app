<?php

namespace Tests\Feature;

use App\Enums\MealPlanStatus;
use App\Models\Household;
use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

class MealPlanApiTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    public function test_index_lists_only_the_households_plans(): void
    {
        $this->actingInHousehold();
        MealPlan::factory()->create([
            'household_id' => $this->household->id,
            'week_start_date' => '2026-06-01',
        ]);

        // Another household's plan must be hidden by the global scope.
        $other = Household::factory()->create();
        MealPlan::factory()->create([
            'household_id' => $other->id,
            'week_start_date' => '2026-06-08',
        ]);

        $response = $this->getJson('/api/v1/meal-plans');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('2026-06-01', $response->json('data.0.week_start_date'));
    }

    public function test_store_creates_a_plan_for_the_household(): void
    {
        $this->actingInHousehold();

        $response = $this->postJson('/api/v1/meal-plans', [
            'week_start_date' => '2026-06-15',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.week_start_date', '2026-06-15');
        $response->assertJsonPath('data.status', MealPlanStatus::Planning->value);

        $this->assertDatabaseHas('meal_plans', [
            'household_id' => $this->household->id,
            'week_start_date' => '2026-06-15',
            'status' => MealPlanStatus::Planning->value,
        ]);
    }

    public function test_store_rejects_a_duplicate_week_start_for_the_same_household(): void
    {
        $this->actingInHousehold();
        MealPlan::factory()->create([
            'household_id' => $this->household->id,
            'week_start_date' => '2026-06-15',
        ]);

        $response = $this->postJson('/api/v1/meal-plans', [
            'week_start_date' => '2026-06-15',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('week_start_date');
    }

    public function test_show_eager_loads_entries(): void
    {
        $this->actingInHousehold();
        $plan = MealPlan::factory()->create(['household_id' => $this->household->id]);

        $response = $this->getJson("/api/v1/meal-plans/{$plan->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $plan->id);
        $response->assertJsonStructure(['data' => ['id', 'week_start_date', 'status', 'entries']]);
    }

    public function test_update_changes_status(): void
    {
        $this->actingInHousehold();
        $plan = MealPlan::factory()->create([
            'household_id' => $this->household->id,
            'week_start_date' => '2026-06-15',
        ]);

        $response = $this->putJson("/api/v1/meal-plans/{$plan->id}", [
            'week_start_date' => '2026-06-15',
            'status' => MealPlanStatus::Active->value,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', MealPlanStatus::Active->value);
    }

    public function test_destroy_deletes_the_plan(): void
    {
        $this->actingInHousehold();
        $plan = MealPlan::factory()->create(['household_id' => $this->household->id]);

        $this->deleteJson("/api/v1/meal-plans/{$plan->id}")->assertNoContent();

        $this->assertDatabaseMissing('meal_plans', ['id' => $plan->id]);
    }

    public function test_cannot_view_another_households_plan(): void
    {
        $this->actingInHousehold();

        $other = Household::factory()->create();
        $plan = MealPlan::factory()->create(['household_id' => $other->id]);

        // Global scope hides the row, so route-model binding 404s.
        $this->getJson("/api/v1/meal-plans/{$plan->id}")->assertNotFound();
    }
}
