<?php

namespace Tests\Feature;

use App\Enums\InventoryLocation;
use App\Enums\InventoryStatus;
use App\Models\Household;
use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

class InventoryIndexTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    public function test_index_lists_only_the_current_households_lots(): void
    {
        $this->actingInHousehold();

        $mine = InventoryItem::factory()->create(['household_id' => $this->household->id]);

        $otherHousehold = Household::factory()->create();
        InventoryItem::factory()->create(['household_id' => $otherHousehold->id]);

        $response = $this->getJson('/api/v1/inventory-items');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($mine->id));
        $this->assertCount(1, $ids);
    }

    public function test_index_filters_by_location_and_status(): void
    {
        $this->actingInHousehold();

        $fridgeActive = InventoryItem::factory()->create([
            'household_id' => $this->household->id,
            'location' => InventoryLocation::Fridge,
            'status' => InventoryStatus::Active,
        ]);
        InventoryItem::factory()->create([
            'household_id' => $this->household->id,
            'location' => InventoryLocation::Pantry,
            'status' => InventoryStatus::Active,
        ]);
        InventoryItem::factory()->create([
            'household_id' => $this->household->id,
            'location' => InventoryLocation::Fridge,
            'status' => InventoryStatus::Used,
        ]);

        $response = $this->getJson('/api/v1/inventory-items?location=fridge&status=active');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertCount(1, $ids);
        $this->assertTrue($ids->contains($fridgeActive->id));
    }
}
