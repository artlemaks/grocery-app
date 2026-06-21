<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\InventoryItem;
use App\Services\Inventory\InventoryActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

/**
 * Phase 2 reconciliation: freeze pauses + extends the clock, thaw resumes it and restarts the
 * opened window, discard is user-only, and the weekly reconcile surfaces the right buckets (ADR-0003).
 */
class InventoryReconciliationTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    private function lot(array $overrides = []): InventoryItem
    {
        $ingredient = Ingredient::factory()->create(['household_id' => $this->household->id]);

        return InventoryItem::factory()->create(array_merge([
            'household_id' => $this->household->id,
            'ingredient_id' => $ingredient->id,
        ], $overrides));
    }

    public function test_freeze_pauses_the_clock_and_extends_best_before(): void
    {
        $this->actingInHousehold();
        $lot = $this->lot(['effective_best_before' => now()->addDays(2)->toDateString()]);

        app(InventoryActionService::class)->freeze($lot);
        $lot->refresh();

        $this->assertSame('frozen', $lot->status->value);
        $this->assertGreaterThanOrEqual(1, $lot->frozen_days_remaining);
        $this->assertNotNull($lot->frozen_on);
        // Displayed best-before is pushed well out while frozen.
        $this->assertTrue($lot->effective_best_before->greaterThan(now()->addDays(80)));
    }

    public function test_thaw_resumes_the_clock_and_restarts_the_opened_window(): void
    {
        $this->actingInHousehold();
        $ingredient = Ingredient::factory()->create([
            'household_id' => $this->household->id,
            'use_within_after_open_days' => 5,
        ]);
        $lot = InventoryItem::factory()->create([
            'household_id' => $this->household->id,
            'ingredient_id' => $ingredient->id,
            'status' => 'frozen',
            'frozen_days_remaining' => 10,
            'is_opened' => true,
            'opened_on' => now()->subDays(30)->toDateString(),
            'effective_best_before' => now()->addDays(90)->toDateString(),
        ]);

        app(InventoryActionService::class)->thaw($lot);
        $lot->refresh();

        $this->assertSame('active', $lot->status->value);
        // Opened window restarted at thaw time.
        $this->assertSame(now()->toDateString(), $lot->opened_on->toDateString());
        // Effective = earlier of resumed sealed (today+10) and opened (today+5) → today+5.
        $this->assertSame(now()->addDays(5)->toDateString(), $lot->effective_best_before->toDateString());
        $this->assertNull($lot->frozen_days_remaining);
    }

    public function test_discard_is_user_driven_and_records_the_date(): void
    {
        $this->actingInHousehold();
        $lot = $this->lot();

        $this->postJson("/api/v1/inventory-items/{$lot->id}/discard")->assertOk();

        $lot->refresh();
        $this->assertSame('discarded', $lot->status->value);
        $this->assertNotNull($lot->discarded_on);
    }

    public function test_open_action_starts_the_opened_clock(): void
    {
        $this->actingInHousehold();
        $ingredient = Ingredient::factory()->create([
            'household_id' => $this->household->id,
            'use_within_after_open_days' => 3,
        ]);
        $lot = InventoryItem::factory()->create([
            'household_id' => $this->household->id,
            'ingredient_id' => $ingredient->id,
            'is_opened' => false,
        ]);

        $this->postJson("/api/v1/inventory-items/{$lot->id}/open")->assertOk();

        $lot->refresh();
        $this->assertTrue($lot->is_opened);
        $this->assertSame(now()->addDays(3)->toDateString(), $lot->effective_best_before->toDateString());
    }

    public function test_reconcile_surfaces_expiring_freeze_and_discard_buckets(): void
    {
        $this->actingInHousehold();
        $freezable = Ingredient::factory()->create(['household_id' => $this->household->id, 'freezable' => true]);

        // Expiring soon (today+1) → expiring + freeze suggestion.
        InventoryItem::factory()->create([
            'household_id' => $this->household->id, 'ingredient_id' => $freezable->id,
            'location' => 'fridge', 'status' => 'active', 'effective_best_before' => now()->addDay()->toDateString(),
        ]);
        // Past best-before → discard candidate (also counts as expiring).
        InventoryItem::factory()->create([
            'household_id' => $this->household->id, 'ingredient_id' => $freezable->id,
            'location' => 'fridge', 'status' => 'active', 'effective_best_before' => now()->subDay()->toDateString(),
        ]);

        $this->get('/reconcile')->assertInertia(fn (Assert $page) => $page
            ->component('Reconcile/Index', false)
            ->has('freezeSuggestions', 2)   // both active, freezable, fridge, expiring
            ->has('discardCandidates', 1)   // only the past one
            ->has('stock', 2)
        );
    }
}
