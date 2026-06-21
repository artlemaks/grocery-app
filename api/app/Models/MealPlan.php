<?php

namespace App\Models;

use App\Enums\MealPlanStatus;
use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealPlan extends Model
{
    use BelongsToHousehold;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'week_start_date',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
            'status' => MealPlanStatus::class,
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(MealPlanEntry::class);
    }

    public function shoppingLists(): HasMany
    {
        return $this->hasMany(ShoppingList::class);
    }
}
