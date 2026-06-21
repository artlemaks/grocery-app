<?php

namespace App\Enums;

/** Lifecycle of an inventory lot. Only the user moves a lot to Discarded (ADR-0003). */
enum InventoryStatus: string
{
    case Active = 'active';
    case Frozen = 'frozen';
    case Used = 'used';
    case Discarded = 'discarded';
}
