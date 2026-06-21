<?php

namespace App\Http\Controllers\Web;

use App\Enums\MealPlanStatus;
use App\Http\Controllers\Controller;
use App\Models\MealPlan;
use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Models\Tag;
use App\Services\Planning\MealSplitResolver;
use App\Services\Shopping\ShoppingListGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlannerController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', MealPlan::class);

        $weekStart = Carbon::now()->startOfWeek();

        // Find-or-create this household's plan for the current week.
        $plan = MealPlan::firstOrCreate(
            ['week_start_date' => $weekStart->toDateString()],
            ['status' => MealPlanStatus::Planning],
        );
        $plan->load(['entries.recipe', 'entries.slotTag']);

        $slots = Tag::whereIn('name', ['Breakfast', 'Lunch', 'Dinner'])->orderBy('id')->get();

        return Inertia::render('Planner/Index', [
            'plan' => [
                'id' => $plan->id,
                'week_start_date' => $plan->week_start_date->toDateString(),
                'status' => $plan->status->value,
            ],
            'days' => collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i)->toDateString()),
            'slots' => $slots->map(fn (Tag $t) => ['id' => $t->id, 'name' => $t->name]),
            'entries' => $plan->entries->map(fn (MealPlanEntry $e) => [
                'id' => $e->id,
                'date' => $e->date->toDateString(),
                'slot_tag_id' => $e->slot_tag_id,
                'is_split' => $e->is_split,
                'recipe' => ['id' => $e->recipe->id, 'name' => $e->recipe->name],
            ]),
            'recipes' => Recipe::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeEntry(Request $request, MealPlan $mealPlan, MealSplitResolver $resolver): RedirectResponse
    {
        $this->authorize('update', $mealPlan);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'slot_tag_id' => ['required', Rule::exists('tags', 'id')->where('household_id', $request->user()->household_id)],
            'recipe_id' => ['required', Rule::exists('recipes', 'id')->where('household_id', $request->user()->household_id)],
        ]);

        $recipe = Recipe::findOrFail($data['recipe_id']);
        $split = $resolver->resolve($recipe, $mealPlan->household->users);

        $mealPlan->entries()->create([
            ...$data,
            'is_split' => $split['is_split'],
            'members' => $split['members'],
        ]);

        return back()->with('success', 'Meal added.');
    }

    public function destroyEntry(MealPlan $mealPlan, MealPlanEntry $entry): RedirectResponse
    {
        $this->authorize('update', $mealPlan);
        abort_unless($entry->meal_plan_id === $mealPlan->id, 404);

        $entry->delete();

        return back();
    }

    public function generate(MealPlan $mealPlan, ShoppingListGenerationService $generation): RedirectResponse
    {
        $this->authorize('update', $mealPlan);

        $generation->generate($mealPlan->load('entries.recipe', 'household.users'));

        return redirect('/shopping')->with('success', 'Shopping list generated.');
    }
}
