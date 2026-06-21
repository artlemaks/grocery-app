<?php

namespace App\Services\Ai;

/**
 * Deterministic LLM stand-in used when no API key is configured and in tests. Returns queued
 * responses if any were pushed; otherwise synthesizes a value from the JSON schema so callers
 * always get well-shaped output without spending tokens.
 */
class FakeLlmClient implements LlmClient
{
    /** @var list<array<string, mixed>> */
    public array $responses = [];

    /** @var list<array{system: string, prompt: string, schema: array<string, mixed>}> */
    public array $calls = [];

    /**
     * Queue the next response(s) the fake will return, in order.
     *
     * @param  array<string, mixed>  $response
     */
    public function push(array $response): void
    {
        $this->responses[] = $response;
    }

    public function structured(string $system, string $prompt, array $schema): array
    {
        $this->calls[] = ['system' => $system, 'prompt' => $prompt, 'schema' => $schema];

        if ($this->responses !== []) {
            return array_shift($this->responses);
        }

        return $this->synthesize($schema);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return mixed
     */
    private function synthesize(array $schema): mixed
    {
        return match ($schema['type'] ?? 'string') {
            'object' => collect($schema['properties'] ?? [])
                ->map(fn ($prop) => $this->synthesize($prop))
                ->all(),
            'array' => [$this->synthesize($schema['items'] ?? ['type' => 'string'])],
            'integer', 'number' => 1,
            'boolean' => true,
            default => 'sample',
        };
    }
}
