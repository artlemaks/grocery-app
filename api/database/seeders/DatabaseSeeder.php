<?php

namespace Database\Seeders;

use App\Enums\DietClass;
use App\Enums\DietType;
use App\Models\Household;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the confirmed Larder household (Artur omnivore + Jolene pescatarian) with base
     * tags and a little sample data so the web client has something to show.
     * Login: artur@larder.test / password
     */
    public function run(): void
    {
        $household = Household::create(['name' => 'The Larder Household']);

        User::create([
            'name' => 'Artur',
            'email' => 'artur@larder.test',
            'password' => Hash::make('password'),
            'household_id' => $household->id,
            'diet_type' => DietType::Omnivore,
        ]);

        User::create([
            'name' => 'Jolene',
            'email' => 'jolene@larder.test',
            'password' => Hash::make('password'),
            'household_id' => $household->id,
            'diet_type' => DietType::Pescatarian,
        ]);

        TagSeeder::seedFor($household);

        // Ingredients (incl. a meat → veg substitute pair).
        $vegMince = $this->ingredient($household, 'Vegetarian Mince', DietClass::Plant);
        $this->ingredient($household, 'Mince Beef', DietClass::Meat, substitute: $vegMince);
        $this->ingredient($household, 'Spaghetti', DietClass::Plant);
        $this->ingredient($household, 'Salmon', DietClass::Fish);
        $this->ingredient($household, 'Granola', DietClass::Plant);
        $this->ingredient($household, 'Yoghurt', DietClass::Dairy, openTracking: true);

        // Sample recipes.
        $bolognese = Recipe::create([
            'household_id' => $household->id,
            'name' => 'Spaghetti Bolognese',
            'servings_default' => 2,
        ]);
        $bolognese->recipeIngredients()->createMany([
            ['ingredient_id' => Ingredient::where('household_id', $household->id)->where('name', 'Mince Beef')->value('id')],
            ['ingredient_id' => Ingredient::where('household_id', $household->id)->where('name', 'Spaghetti')->value('id')],
        ]);
        $bolognese->tags()->attach($household->tags()->where('name', 'Dinner')->value('id'));

        $granola = Recipe::create([
            'household_id' => $household->id,
            'name' => 'Granola & Yoghurt',
            'servings_default' => 2,
        ]);
        $granola->recipeIngredients()->createMany([
            ['ingredient_id' => Ingredient::where('household_id', $household->id)->where('name', 'Granola')->value('id')],
            ['ingredient_id' => Ingredient::where('household_id', $household->id)->where('name', 'Yoghurt')->value('id')],
        ]);
        $granola->tags()->attach($household->tags()->where('name', 'Breakfast')->value('id'));
    }

    private function ingredient(Household $household, string $name, DietClass $class, ?Ingredient $substitute = null, bool $openTracking = false): Ingredient
    {
        return Ingredient::create([
            'household_id' => $household->id,
            'name' => $name,
            'diet_class' => $class,
            'substitute_ingredient_id' => $substitute?->id,
            'requires_open_tracking' => $openTracking,
            'shelf_life_sealed_days' => $openTracking ? 21 : null,
            'use_within_after_open_days' => $openTracking ? 5 : null,
        ]);
    }
}
