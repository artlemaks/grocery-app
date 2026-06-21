<?php

namespace App\Services\Ai;

/**
 * Provider-agnostic LLM access. All AI features go through this interface so the real Anthropic
 * client and the deterministic test fake are interchangeable (ADR-0004). Structured output only —
 * every AI task in Larder wants a JSON object matching a schema.
 */
interface LlmClient
{
    /**
     * Return a JSON object matching $schema, produced from the system + user prompt.
     *
     * @param  array<string, mixed>  $schema  a JSON Schema (object at the root)
     * @return array<string, mixed>
     */
    public function structured(string $system, string $prompt, array $schema): array;
}
