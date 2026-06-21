<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Child row recording consumption of an inventory item, optionally tied to a meal plan entry.
 */
class UsageLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'inventory_item_id',
        'meal_plan_entry_id',
        'amount_used',
        'logged_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_used' => 'decimal:2',
            'logged_at' => 'datetime',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function mealPlanEntry(): BelongsTo
    {
        return $this->belongsTo(MealPlanEntry::class);
    }
}
