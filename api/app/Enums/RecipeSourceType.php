<?php

namespace App\Enums;

/** How a recipe was created. AI imports (photo/url) land as drafts for review (Phase 3). */
enum RecipeSourceType: string
{
    case Manual = 'manual';
    case Photo = 'photo';
    case Url = 'url';
}
