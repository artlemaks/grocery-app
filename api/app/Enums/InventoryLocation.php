<?php

namespace App\Enums;

/** Where an inventory lot physically lives. Freezer pauses the best-before clocks (ADR-0003). */
enum InventoryLocation: string
{
    case Fridge = 'fridge';
    case Pantry = 'pantry';
    case Freezer = 'freezer';
}
