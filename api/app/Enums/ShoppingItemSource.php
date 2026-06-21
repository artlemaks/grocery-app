<?php

namespace App\Enums;

/** Where a shopping-list line came from. */
enum ShoppingItemSource: string
{
    case Plan = 'plan';
    case Manual = 'manual';
}
