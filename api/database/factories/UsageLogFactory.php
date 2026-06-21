<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\UsageLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageLog>
 */
class UsageLogFactory extends Factory
{
    protected $model = UsageLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'meal_plan_entry_id' => null,
            'amount_used' => 0.33,
            'logged_at' => now(),
        ];
    }
}
