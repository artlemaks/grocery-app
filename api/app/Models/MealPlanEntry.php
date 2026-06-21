<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Child row of a meal plan. Scoped via its parent plan (no household_id of its own).
 */
class MealPlanEntry extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'meal_plan_id',
        'date',
        'slot_tag_id',
        'recipe_id',
        'is_split',
        'members',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_split' => 'boolean',
            'members' => 'array',
        ];
    }

    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    public function slotTag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'slot_tag_id');
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(UsageLog::class);
    }
}
