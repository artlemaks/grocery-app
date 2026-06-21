<?php

namespace App\Enums;

/**
 * The diet-relevant class of an ingredient. A member's diet excludes a set of these
 * classes, which is what triggers a vegetarian/diet substitute (ADR-0002).
 */
enum DietClass: string
{
    case Meat = 'meat';
    case Fish = 'fish';
    case Dairy = 'dairy';
    case Egg = 'egg';
    case Plant = 'plant';
    case Other = 'other';
}
