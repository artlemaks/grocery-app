<?php

namespace Tests\Feature;

use App\Enums\MealPlanStatus;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

class DuplicateWeekTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    public function test_duplicating_copies_entries_with_dates_shifted_by_seven_days(): void
    {
        $this->actingInHousehold();

        $plan = MealPlan::factory()->create([
            'household_id' => $this->household->id,
            'week_start_date' => '2026-06-01',
        ]);
        $tag = Tag::factory()->create(['household_id' => $this->household->id]);
        $recipe = Recipe::factory()->create(['household_id' => $this->household->id]);

        $plan->entries()->create([
            'date' => '2026-06-01',
            'slot_tag_id' => $tag->id,
            'recipe_id' => $recipe->id,
            'is_split' => true,
            'members' => [$this->me->id],
        ]);
        $plan->entries()->create([
            'date' => '2026-06-03',
            'slot_tag_id' => $tag->id,
            'recipe_id' => $recipe->id,
            'is_split' => false,
            'members' => [$this->me->id],
        ]);

        $response = $this->postJson("/api/v1/meal-plans/{$plan->id}/duplicate", [
            'week_start_date' => '2026-06-08',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.week_start_date', '2026-06-08');
        $response->assertJsonPath('data.status', MealPlanStatus::Planning->value);

        $newPlanId = $response->json('data.id');
        $this->assertNotSame($plan->id, $newPlanId);

        // Entries copied with +7 day shift, preserving slot/recipe/is_split/members.
        $this->assertDatabaseHas('meal_plan_entries', [
            'meal_plan_id' => $newPlanId,
            'date' => '2026-06-08',
            'slot_tag_id' => $tag->id,
            'recipe_id' => $recipe->id,
            'is_split' => true,
        ]);
        $this->assertDatabaseHas('meal_plan_entries', [
            'meal_plan_id' => $newPlanId,
            'date' => '2026-06-10',
            'slot_tag_id' => $tag->id,
            'recipe_id' => $recipe->id,
            'is_split' => false,
        ]);

        $this->assertCount(2, $response->json('data.entries'));
    }
}
