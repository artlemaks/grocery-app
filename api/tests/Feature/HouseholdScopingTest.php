<?php

namespace Tests\Feature;

use App\Enums\DietClass;
use App\Models\Household;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Multi-tenancy: the BelongsToHousehold global scope must isolate one household's
 * data from another, and auto-fill household_id from the acting user (ADR-0004).
 */
class HouseholdScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingredients_are_scoped_to_the_authenticated_users_household(): void
    {
        $householdA = Household::create(['name' => 'Household A']);
        $householdB = Household::create(['name' => 'Household B']);
        $userA = User::factory()->create(['household_id' => $householdA->id]);
        $userB = User::factory()->create(['household_id' => $householdB->id]);

        $this->actingAs($userA);
        Ingredient::create(['name' => 'Mince Beef', 'diet_class' => DietClass::Meat]);

        $this->actingAs($userB);
        Ingredient::create(['name' => 'Tofu', 'diet_class' => DietClass::Plant]);

        // User A sees only household A's stock.
        $this->actingAs($userA);
        $this->assertSame(1, Ingredient::count());
        $seenByA = Ingredient::first();
        $this->assertSame('Mince Beef', $seenByA->name);
        $this->assertSame($householdA->id, $seenByA->household_id);

        // User B sees only household B's stock.
        $this->actingAs($userB);
        $this->assertSame(1, Ingredient::count());
        $this->assertSame('Tofu', Ingredient::first()->name);
    }

    public function test_household_id_is_autofilled_from_the_acting_user(): void
    {
        $household = Household::create(['name' => 'Auto']);
        $user = User::factory()->create(['household_id' => $household->id]);

        $this->actingAs($user);
        $ingredient = Ingredient::create(['name' => 'Spaghetti', 'diet_class' => DietClass::Plant]);

        $this->assertSame($household->id, $ingredient->fresh()->household_id);
    }
}
