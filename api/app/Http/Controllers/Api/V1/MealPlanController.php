<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MealPlanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MealPlan\StoreMealPlanRequest;
use App\Http\Requests\MealPlan\UpdateMealPlanRequest;
use App\Http\Resources\MealPlanResource;
use App\Models\MealPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MealPlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', MealPlan::class);

        return MealPlanResource::collection(
            MealPlan::query()->orderBy('week_start_date')->get()
        );
    }

    public function store(StoreMealPlanRequest $request): MealPlanResource
    {
        $this->authorize('create', MealPlan::class);

        $mealPlan = MealPlan::create([
            'week_start_date' => $request->validated('week_start_date'),
            'status' => $request->validated('status') ?? MealPlanStatus::Planning,
        ]);

        return new MealPlanResource($mealPlan);
    }

    public function show(MealPlan $mealPlan): MealPlanResource
    {
        $this->authorize('view', $mealPlan);

        $mealPlan->load('entries.recipe', 'entries.slotTag');

        return new MealPlanResource($mealPlan);
    }

    public function update(UpdateMealPlanRequest $request, MealPlan $mealPlan): MealPlanResource
    {
        $this->authorize('update', $mealPlan);

        $mealPlan->update([
            'week_start_date' => $request->validated('week_start_date'),
            'status' => $request->validated('status') ?? MealPlanStatus::Planning,
        ]);

        return new MealPlanResource($mealPlan);
    }

    public function destroy(MealPlan $mealPlan): \Illuminate\Http\Response
    {
        $this->authorize('delete', $mealPlan);

        $mealPlan->delete();

        return response()->noContent();
    }

    /**
     * Duplicate a plan into a new week, copying every entry with its date shifted by the
     * week delta (reuse-last-week).
     */
    public function duplicate(Request $request, MealPlan $mealPlan): MealPlanResource
    {
        $this->authorize('update', $mealPlan);

        $validated = $request->validate([
            'week_start_date' => ['required', 'date'],
        ]);

        $newWeekStart = \Illuminate\Support\Carbon::parse($validated['week_start_date'])->startOfDay();
        $dayOffset = (int) $mealPlan->week_start_date->copy()->startOfDay()
            ->diffInDays($newWeekStart, false);

        $newPlan = MealPlan::create([
            'week_start_date' => $newWeekStart->toDateString(),
            'status' => MealPlanStatus::Planning,
        ]);

        foreach ($mealPlan->entries()->get() as $entry) {
            $newPlan->entries()->create([
                'date' => $entry->date->copy()->addDays($dayOffset)->toDateString(),
                'slot_tag_id' => $entry->slot_tag_id,
                'recipe_id' => $entry->recipe_id,
                'is_split' => $entry->is_split,
                'members' => $entry->members,
            ]);
        }

        $newPlan->load('entries');

        return new MealPlanResource($newPlan);
    }
}
