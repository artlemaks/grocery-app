<?php

return [
    // Provider + model for AI features. Default to the most capable model; switch to a cheaper
    // one (e.g. claude-haiku-4-5) via AI_MODEL when cost matters more than quality (spec §6).
    'provider' => env('AI_PROVIDER', 'anthropic'),
    'api_key' => env('ANTHROPIC_API_KEY'),
    'model' => env('AI_MODEL', 'claude-opus-4-8'),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 4096),

    // When no API key is configured the app binds a deterministic FakeLlmClient, so AI features
    // are exercised end-to-end (and tested) without spending tokens. Set ANTHROPIC_API_KEY to
    // switch to the real Anthropic client — no code change required.
];
