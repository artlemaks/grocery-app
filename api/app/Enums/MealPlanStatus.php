<?php

namespace App\Enums;

/** Lifecycle of a weekly meal plan. */
enum MealPlanStatus: string
{
    case Planning = 'planning';
    case Active = 'active';
    case Closed = 'closed';
}
