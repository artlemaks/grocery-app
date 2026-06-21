<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Models\Household;
use App\Services\Ai\MealSuggester;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Async AI meal suggestions (spec §5.8). Writes the suggestion list to the AiJob envelope.
 */
class SuggestMealsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $aiJobId) {}

    public function handle(MealSuggester $suggester): void
    {
        $job = AiJob::withoutGlobalScopes()->findOrFail($this->aiJobId);
        $job->markProcessing();

        try {
            $household = Household::findOrFail($job->household_id);
            $job->markCompleted($suggester->suggest($household));
        } catch (Throwable $e) {
            $job->markFailed($e->getMessage());
        }
    }
}
