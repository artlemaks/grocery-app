<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AiJobStatus;
use App\Enums\AiJobType;
use App\Http\Controllers\Controller;
use App\Jobs\ImportRecipeFromUrlJob;
use App\Jobs\SuggestMealsJob;
use App\Models\AiJob;
use App\Models\MealPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AI capture & suggestions (Phase 3). Each endpoint enqueues a job and returns an AiJob handle;
 * the client polls `show` for the result. All AI work is async (spec §6).
 */
class AiController extends Controller
{
    public function importRecipeUrl(Request $request): JsonResponse
    {
        $this->authorize('create', AiJob::class);

        $data = $request->validate(['url' => ['required', 'url']]);

        $job = AiJob::create([
            'type' => AiJobType::ImportRecipeFromUrl,
            'status' => AiJobStatus::Pending,
            'input' => ['url' => $data['url']],
        ]);

        ImportRecipeFromUrlJob::dispatch($job->id);

        return $this->respond($job->refresh(), 202);
    }

    public function suggestMeals(MealPlan $mealPlan): JsonResponse
    {
        $this->authorize('create', AiJob::class);

        $job = AiJob::create([
            'type' => AiJobType::SuggestMeals,
            'status' => AiJobStatus::Pending,
            'input' => ['meal_plan_id' => $mealPlan->id],
        ]);

        SuggestMealsJob::dispatch($job->id);

        return $this->respond($job->refresh(), 202);
    }

    public function show(AiJob $aiJob): JsonResponse
    {
        $this->authorize('view', $aiJob);

        return $this->respond($aiJob);
    }

    private function respond(AiJob $job, int $status = 200): JsonResponse
    {
        return response()->json([
            'id' => $job->id,
            'type' => $job->type->value,
            'status' => $job->status->value,
            'result' => $job->result,
            'error' => $job->error,
        ], $status);
    }
}
