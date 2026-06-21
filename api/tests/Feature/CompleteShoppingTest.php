<?php

namespace Tests\Feature;

use App\Enums\InventoryStatus;
use App\Enums\ShoppingListStatus;
use App\Models\Ingredient;
use App\Models\InventoryItem;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

class CompleteShoppingTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    public function test_completing_a_list_converts_checked_items_to_inventory_lots(): void
    {
        $this->actingInHousehold();

        $checkedIngredient = Ingredient::factory()->create([
            'household_id' => $this->household->id,
            'shelf_life_sealed_days' => 10,
        ]);
        $uncheckedIngredient = Ingredient::factory()->create([
            'household_id' => $this->household->id,
        ]);

        $list = ShoppingList::factory()->create(['household_id' => $this->household->id]);
        $checkedItem = ShoppingListItem::factory()->create([
            'shopping_list_id' => $list->id,
            'ingredient_id' => $checkedIngredient->id,
            'is_checked' => true,
        ]);
        $uncheckedItem = ShoppingListItem::factory()->create([
            'shopping_list_id' => $list->id,
            'ingredient_id' => $uncheckedIngredient->id,
            'is_checked' => false,
        ]);

        $response = $this->postJson("/api/v1/shopping-lists/{$list->id}/complete");

        $response->assertOk();

        $lot = InventoryItem::where('ingredient_id', $checkedIngredient->id)->first();
        $this->assertNotNull($lot);
        $this->assertSame(1.0, (float) $lot->remaining);
        $this->assertSame(InventoryStatus::Active, $lot->status);
        $this->assertSame(
            now()->copy()->addDays(10)->toDateString(),
            $lot->sealed_best_before->toDateString(),
        );

        $this->assertSame(ShoppingListStatus::Completed, $list->fresh()->status);

        $this->assertNull(InventoryItem::where('ingredient_id', $uncheckedIngredient->id)->first());
        $this->assertDatabaseHas('shopping_list_items', ['id' => $uncheckedItem->id]);
    }
}
