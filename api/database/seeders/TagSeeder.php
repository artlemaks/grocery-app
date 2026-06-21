<?php

namespace Database\Seeders;

use App\Models\Household;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Tags are household-scoped, so the global seeder does nothing on its own.
     * Use seedFor() to seed the default slot tags for a specific household.
     */
    public function run(): void
    {
        //
    }

    /**
     * Seed the three default slot tags for the given household.
     */
    public static function seedFor(Household $household): void
    {
        foreach (['Breakfast', 'Lunch', 'Dinner'] as $name) {
            $household->tags()->firstOrCreate(['name' => $name]);
        }
    }
}
