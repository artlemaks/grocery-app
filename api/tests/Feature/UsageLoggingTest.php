<?php

namespace Tests\Feature;

use App\Enums\InventoryStatus;
use App\Models\Ingredient;
use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

class UsageLoggingTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    public function test_logging_usage_decrements_opens_the_lot_and_records_a_usage_log(): void
    {
        $this->actingInHousehold();

        $ingredient = Ingredient::factory()->withOpenTracking()->create([
            'household_id' => $this->household->id,
        ]);
        $lot = InventoryItem::factory()->create([
            'household_id' => $this->household->id,
            'ingredient_id' => $ingredient->id,
            'remaining' => 1.00,
            'is_opened' => false,
        ]);

        $response = $this->postJson("/api/v1/inventory-items/{$lot->id}/usage", [
            'amount' => 0.33,
        ]);

        $response->assertOk();

        $lot->refresh();
        $this->assertSame(0.67, (float) $lot->remaining);
        $this->assertTrue((bool) $lot->is_opened);
        $this->assertNotNull($lot->opened_on);
        $this->assertDatabaseHas('usage_logs', ['inventory_item_id' => $lot->id]);
    }

    public function test_logging_the_rest_marks_the_lot_used(): void
    {
        $this->actingInHousehold();

        $ingredient = Ingredient::factory()->withOpenTracking()->create([
            'household_id' => $this->household->id,
        ]);
        $lot = InventoryItem::factory()->create([
            'household_id' => $this->household->id,
            'ingredient_id' => $ingredient->id,
            'remaining' => 1.00,
        ]);

        $this->postJson("/api/v1/inventory-items/{$lot->id}/usage", ['amount' => 0.33])->assertOk();
        $this->postJson("/api/v1/inventory-items/{$lot->id}/usage", ['amount' => 0.67])->assertOk();

        $lot->refresh();
        $this->assertSame(0.0, (float) $lot->remaining);
        $this->assertSame(InventoryStatus::Used, $lot->status);
    }
}
