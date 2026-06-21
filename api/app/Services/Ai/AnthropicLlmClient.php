<?php

namespace App\Services\Ai;

use Anthropic\Client;

/**
 * Real LLM access via the official Anthropic PHP SDK, using the Messages API with structured
 * (json_schema) output so responses are guaranteed to parse against the caller's schema.
 */
class AnthropicLlmClient implements LlmClient
{
    public function __construct(
        private Client $client,
        private string $model,
        private int $maxTokens = 4096,
    ) {}

    public function structured(string $system, string $prompt, array $schema): array
    {
        $message = $this->client->messages->create(
            model: $this->model,
            maxTokens: $this->maxTokens,
            system: $system,
            messages: [
                ['role' => 'user', 'content' => $prompt],
            ],
            outputConfig: [
                'format' => [
                    'type' => 'json_schema',
                    'schema' => $schema,
                ],
            ],
        );

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                return json_decode($block->text, true) ?? [];
            }
        }

        return [];
    }
}
