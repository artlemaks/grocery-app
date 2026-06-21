<?php

namespace App\Enums;

/** Lifecycle of a shopping list. "Complete shopping" moves it to Completed. */
enum ShoppingListStatus: string
{
    case Draft = 'draft';
    case Shopping = 'shopping';
    case Completed = 'completed';
}
