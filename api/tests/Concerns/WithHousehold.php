<?php

namespace Tests\Concerns;

use App\Enums\DietType;
use App\Models\Household;
use App\Models\User;

/**
 * Test helper: create a household with members and authenticate as one of them, so the
 * BelongsToHousehold global scope and policies have a tenant context.
 */
trait WithHousehold
{
    protected Household $household;

    protected User $me;

    /**
     * Create a household + an authenticated member. Returns the acting user.
     */
    protected function actingInHousehold(DietType $diet = DietType::Omnivore): User
    {
        $this->household = Household::factory()->create();
        $this->me = User::factory()->create([
            'household_id' => $this->household->id,
            'diet_type' => $diet,
        ]);
        $this->actingAs($this->me);

        return $this->me;
    }

    /**
     * Add another member to the current household (e.g. the pescatarian partner).
     */
    protected function addMember(DietType $diet): User
    {
        return User::factory()->create([
            'household_id' => $this->household->id,
            'diet_type' => $diet,
        ]);
    }
}
