<?php

namespace App\Providers;

use Anthropic\Client;
use App\Services\Ai\AnthropicLlmClient;
use App\Services\Ai\FakeLlmClient;
use App\Services\Ai\LlmClient;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The fake is a singleton so tests can resolve it, queue responses, and inspect calls.
        $this->app->singleton(FakeLlmClient::class);

        $this->app->singleton(LlmClient::class, function ($app) {
            $key = config('ai.api_key');

            // No key configured → deterministic fake. Add ANTHROPIC_API_KEY to use the real client.
            if (! $key) {
                return $app->make(FakeLlmClient::class);
            }

            return new AnthropicLlmClient(
                new Client(apiKey: $key),
                config('ai.model'),
                (int) config('ai.max_tokens'),
            );
        });
    }
}
