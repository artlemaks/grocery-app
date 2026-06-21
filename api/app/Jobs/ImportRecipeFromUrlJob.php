<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Services\Ai\RecipeUrlImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Async URL recipe import (spec §6). Writes its outcome to the AiJob envelope so the client polls
 * for the resulting draft recipe id.
 */
class ImportRecipeFromUrlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $aiJobId) {}

    public function handle(RecipeUrlImporter $importer): void
    {
        $job = AiJob::withoutGlobalScopes()->findOrFail($this->aiJobId);
        $job->markProcessing();

        try {
            $recipe = $importer->import($job->household_id, $job->input['url']);
            $job->markCompleted(['recipe_id' => $recipe->id]);
        } catch (Throwable $e) {
            $job->markFailed($e->getMessage());
        }
    }
}
