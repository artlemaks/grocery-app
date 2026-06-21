<?php

namespace App\Models\Concerns;

use App\Models\Household;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Multi-tenancy by household (ADR-0004). A model using this trait:
 *  - is automatically query-scoped to the authenticated user's household_id, and
 *  - has household_id auto-filled on create.
 *
 * The data model is multi-tenant from day one so productisation (Phase 5) is a switch, not a remodel.
 */
trait BelongsToHousehold
{
    public static function bootBelongsToHousehold(): void
    {
        static::addGlobalScope('household', function (Builder $builder): void {
            if ($householdId = Auth::user()?->household_id) {
                $builder->where($builder->getModel()->getTable().'.household_id', $householdId);
            }
        });

        static::creating(function (\Illuminate\Database\Eloquent\Model $model): void {
            if (empty($model->getAttribute('household_id')) && ($householdId = Auth::user()?->household_id)) {
                $model->setAttribute('household_id', $householdId);
            }
        });
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
