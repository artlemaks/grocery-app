<?php

namespace Tests\Unit;

use App\Enums\DietClass;
use App\Enums\DietType;
use PHPUnit\Framework\TestCase;

/**
 * Pure-logic coverage for the diet-class exclusion mapping that drives substitute
 * selection (ADR-0002). No database.
 */
class DietTypeTest extends TestCase
{
    public function test_omnivore_excludes_nothing(): void
    {
        $this->assertSame([], DietType::Omnivore->excludedClasses());
        $this->assertFalse(DietType::Omnivore->excludes(DietClass::Meat));
    }

    public function test_pescatarian_excludes_meat_but_allows_fish(): void
    {
        $this->assertTrue(DietType::Pescatarian->excludes(DietClass::Meat));
        $this->assertFalse(DietType::Pescatarian->excludes(DietClass::Fish));
    }

    public function test_vegetarian_excludes_meat_and_fish_only(): void
    {
        $this->assertTrue(DietType::Vegetarian->excludes(DietClass::Meat));
        $this->assertTrue(DietType::Vegetarian->excludes(DietClass::Fish));
        $this->assertFalse(DietType::Vegetarian->excludes(DietClass::Dairy));
    }

    public function test_vegan_excludes_all_animal_classes(): void
    {
        foreach ([DietClass::Meat, DietClass::Fish, DietClass::Dairy, DietClass::Egg] as $class) {
            $this->assertTrue(DietType::Vegan->excludes($class), "vegan should exclude {$class->value}");
        }
        $this->assertFalse(DietType::Vegan->excludes(DietClass::Plant));
    }
}
