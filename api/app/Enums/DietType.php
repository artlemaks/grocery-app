<?php

namespace App\Enums;

/**
 * A member's diet profile type. Maps to the set of ingredient {@see DietClass} values the
 * member excludes, which drives substitute selection in planning/shopping/depletion (ADR-0002).
 *
 * Extensible: add a case + its exclusion set in {@see self::excludedClasses()}.
 */
enum DietType: string
{
    case Omnivore = 'omnivore';
    case Pescatarian = 'pescatarian';
    case Vegetarian = 'vegetarian';
    case Vegan = 'vegan';

    /**
     * Ingredient diet-classes this diet excludes.
     *
     * @return list<DietClass>
     */
    public function excludedClasses(): array
    {
        return match ($this) {
            self::Omnivore => [],
            self::Pescatarian => [DietClass::Meat],
            self::Vegetarian => [DietClass::Meat, DietClass::Fish],
            self::Vegan => [DietClass::Meat, DietClass::Fish, DietClass::Dairy, DietClass::Egg],
        };
    }

    /**
     * Does this diet exclude the given ingredient class (i.e. require a substitute)?
     */
    public function excludes(DietClass $class): bool
    {
        return in_array($class, $this->excludedClasses(), true);
    }
}
