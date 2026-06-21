<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MealPlan\StoreEntryRequest;
use App\Http\Requests\MealPlan\UpdateEntryRequest;
use App\Http\Resources\MealPlanEntryResource;
use App\Models\MealPlan;
use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Services\Planning\MealSplitResolver;

class MealPlanEntryController extends Controller
{
    public function store(StoreEntryRequest $request, MealPlan $mealPlan): MealPlanEntryResource
    {
        $this->authorize('update', $mealPlan);

        $recipe = Recipe::findOrFail($request->validated('recipe_id'));
        $resolved = app(MealSplitResolver::class)->resolve($recipe, $mealPlan->household->users);

        $isSplit = $request->has('is_split') && $request->validated('is_split') !== null
            ? $request->boolean('is_split')
            : $resolved['is_split'];

        $entry = $mealPlan->entries()->create([
            'date' => $request->validated('date'),
            'slot_tag_id' => $request->validated('slot_tag_id'),
            'recipe_id' => $recipe->id,
            'is_split' => $isSplit,
            'members' => $resolved['members'],
        ]);

        return new MealPlanEntryResource($entry);
    }

    public function update(UpdateEntryRequest $request, MealPlan $mealPlan, MealPlanEntry $entry): MealPlanEntryResource
    {
        $this->authorize('update', $mealPlan);

        abort_unless($entry->meal_plan_id === $mealPlan->id, 404);

        $recipe = Recipe::findOrFail($request->validated('recipe_id'));
        $resolved = app(MealSplitResolver::class)->resolve($recipe, $mealPlan->household->users);

        $isSplit = $request->has('is_split') && $request->validated('is_split') !== null
            ? $request->boolean('is_split')
            : $resolved['is_split'];

        $entry->update([
            'date' => $request->validated('date'),
            'slot_tag_id' => $request->validated('slot_tag_id'),
            'recipe_id' => $recipe->id,
            'is_split' => $isSplit,
            'members' => $resolved['members'],
        ]);

        return new MealPlanEntryResource($entry);
    }

    public function destroy(MealPlan $mealPlan, MealPlanEntry $entry): \Illuminate\Http\Response
    {
        $this->authorize('update', $mealPlan);

        abort_unless($entry->meal_plan_id === $mealPlan->id, 404);

        $entry->delete();

        return response()->noContent();
    }
}
