<?php

namespace App\Services\Ai;

use App\Enums\DietClass;
use App\Enums\RecipeSourceType;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Imports a recipe from a URL into a DRAFT recipe for review (indications:
 * deterministic-before-llm, ai-imports-need-review-screen). Tries schema.org Recipe JSON-LD
 * first (fast, free, reliable); falls back to an LLM extraction over the page text.
 */
class RecipeUrlImporter
{
    public function __construct(private LlmClient $llm) {}

    public function import(int $householdId, string $url): Recipe
    {
        $html = Http::timeout(15)->get($url)->body();

        $data = $this->fromSchemaOrg($html) ?? $this->fromLlm($html);

        return $this->createDraft($householdId, $url, $data);
    }

    /**
     * Deterministic path: parse a schema.org Recipe from JSON-LD if present.
     *
     * @return array<string, mixed>|null
     */
    private function fromSchemaOrg(string $html): ?array
    {
        preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $matches);

        foreach ($matches[1] as $json) {
            $decoded = json_decode(trim($json), true);
            if (! is_array($decoded)) {
                continue;
            }

            $nodes = $decoded['@graph'] ?? [$decoded];
            foreach ($nodes as $node) {
                if (! is_array($node) || ! $this->isRecipe($node['@type'] ?? null)) {
                    continue;
                }

                return [
                    'name' => $node['name'] ?? 'Imported recipe',
                    'instructions' => $this->flattenInstructions($node['recipeInstructions'] ?? null),
                    'ingredients' => array_map(
                        fn ($line) => ['name' => is_string($line) ? $line : ''],
                        (array) ($node['recipeIngredient'] ?? []),
                    ),
                    'source' => 'schema_org',
                ];
            }
        }

        return null;
    }

    private function isRecipe(mixed $type): bool
    {
        return is_array($type) ? in_array('Recipe', $type, true) : $type === 'Recipe';
    }

    private function flattenInstructions(mixed $instructions): ?string
    {
        if (is_string($instructions)) {
            return $instructions;
        }

        if (is_array($instructions)) {
            $steps = array_filter(array_map(
                fn ($step) => is_string($step) ? $step : ($step['text'] ?? null),
                $instructions,
            ));

            return $steps === [] ? null : implode("\n", $steps);
        }

        return null;
    }

    /**
     * LLM fallback: extract a simplified recipe from the page text.
     *
     * @return array<string, mixed>
     */
    private function fromLlm(string $html): array
    {
        $text = Str::limit(strip_tags($html), 12000, '');

        $data = $this->llm->structured(
            "You extract recipes from web pages into a household's deliberately simplified model: "
                .'ingredient NAMES only (never gram weights or amounts in the name), a title, and optional steps. '
                .'Use a coarse quantity_hint like "1 pack" or "2" only when obvious.',
            "Extract the recipe from this page text:\n\n".$text,
            self::recipeSchema(),
        );

        $data['source'] = 'llm';

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createDraft(int $householdId, string $url, array $data): Recipe
    {
        $recipe = Recipe::create([
            'household_id' => $householdId,
            'name' => $data['name'] ?? 'Imported recipe',
            'instructions' => $data['instructions'] ?? null,
            'source_type' => RecipeSourceType::Url,
            'source_url' => $url,
            'is_draft' => true,
        ]);

        foreach ($data['ingredients'] ?? [] as $line) {
            $name = trim((string) ($line['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            // Match the household's existing ingredient library, or create a new entry.
            $ingredient = Ingredient::firstOrCreate(
                ['household_id' => $householdId, 'name' => $name],
                ['diet_class' => DietClass::Other],
            );

            $recipe->recipeIngredients()->create([
                'ingredient_id' => $ingredient->id,
                'quantity_hint' => $line['quantity_hint'] ?? null,
            ]);
        }

        return $recipe;
    }

    /**
     * @return array<string, mixed>
     */
    public static function recipeSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'instructions' => ['type' => 'string'],
                'ingredients' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'quantity_hint' => ['type' => 'string'],
                        ],
                        'required' => ['name'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['name', 'ingredients'],
            'additionalProperties' => false,
        ];
    }
}
