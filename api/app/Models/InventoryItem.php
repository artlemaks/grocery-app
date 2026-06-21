<?php

namespace App\Models;

use App\Enums\InventoryLocation;
use App\Enums\InventoryStatus;
use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use BelongsToHousehold, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'ingredient_id',
        'location',
        'remaining',
        'purchased_on',
        'is_opened',
        'opened_on',
        'sealed_best_before',
        'effective_best_before',
        'status',
        'frozen_on',
        'frozen_days_remaining',
        'discarded_on',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'location' => InventoryLocation::class,
            'status' => InventoryStatus::class,
            'remaining' => 'decimal:2',
            'purchased_on' => 'date',
            'opened_on' => 'date',
            'sealed_best_before' => 'date',
            'effective_best_before' => 'date',
            'is_opened' => 'boolean',
            'frozen_on' => 'date',
            'frozen_days_remaining' => 'integer',
            'discarded_on' => 'date',
        ];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(UsageLog::class);
    }
}
