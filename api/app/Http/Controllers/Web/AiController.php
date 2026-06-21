<?php

namespace App\Http\Controllers\Web;

use App\Enums\AiJobStatus;
use App\Enums\AiJobType;
use App\Http\Controllers\Controller;
use App\Jobs\ImportRecipeFromUrlJob;
use App\Jobs\SuggestMealsJob;
use App\Models\AiJob;
use App\Models\MealPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Inertia web entry points for AI features (Phase 3b). Each enqueues a Phase 3a job and flashes
 * an `aiJob` handle ({id, kind}); the Vue page polls `show` (JSON) until the job completes.
 */
class AiController extends Controller
{
    public function importRecipeUrl(Request $request): RedirectResponse
    {
        $this->authorize('create', AiJob::class);

        $data = $request->validate(['url' => ['required', 'url']]);

        $job = AiJob::create([
            'type' => AiJobType::ImportRecipeFromUrl,
            'status' => AiJobStatus::Pending,
            'input' => ['url' => $data['url']],
        ]);

        ImportRecipeFromUrlJob::dispatch($job->id);

        return back()->with('aiJob', ['id' => $job->id, 'kind' => 'import']);
    }

    public function suggestMeals(MealPlan $mealPlan): RedirectResponse
    {
        $this->authorize('create', AiJob::class);

        $job = AiJob::create([
            'type' => AiJobType::SuggestMeals,
            'status' => AiJobStatus::Pending,
            'input' => ['meal_plan_id' => $mealPlan->id],
        ]);

        SuggestMealsJob::dispatch($job->id);

        return back()->with('aiJob', ['id' => $job->id, 'kind' => 'suggest']);
    }

    public function show(AiJob $aiJob): JsonResponse
    {
        $this->authorize('view', $aiJob);

        return response()->json([
            'id' => $aiJob->id,
            'status' => $aiJob->status->value,
            'result' => $aiJob->result,
            'error' => $aiJob->error,
        ]);
    }
}
