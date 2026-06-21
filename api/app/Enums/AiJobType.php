<?php

namespace App\Enums;

enum AiJobType: string
{
    case ImportRecipeFromUrl = 'import_recipe_url';
    case SuggestMeals = 'suggest_meals';
}
