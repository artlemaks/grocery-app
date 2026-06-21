<?php

namespace App\Services\Ai;

use App\Enums\InventoryStatus;
use App\Models\Household;

/**
 * "What should we have?" — suggests meals from the household's own recipes, biased toward using
 * stock already on hand (waste reduction) and respecting every member's diet (ADR-0002).
 * Suggestions are advisory; grounded strictly in the household's data to avoid generic output.
 */
class MealSuggester
{
    public function __construct(private LlmClient $llm) {}

    /**
     * @return array{suggestions: list<array{recipe_name: string, reason: string, uses_stock: bool}>}
     */
    public function suggest(Household $household, int $count = 3): array
    {
        $recipes = $household->recipes()->with('tags')->where('is_draft', false)->get()
            ->map(fn ($r) => $r->name.' ['.$r->tags->pluck('name')->join(', ').']')
            ->all();

        $onHand = $household->inventoryItems()
            ->whereIn('status', [InventoryStatus::Active, InventoryStatus::Frozen])
            ->where('remaining', '>', 0)
            ->with('ingredient')
            ->get()
            ->map(fn ($i) => $i->ingredient?->name)
            ->filter()->unique()->values()->all();

        $diets = $household->users
            ->map(fn ($u) => $u->name.': '.($u->diet_type?->value ?? 'unknown'))
            ->all();

        $context = "Recipes (name [tags]):\n- ".implode("\n- ", $recipes)
            ."\n\nIngredients on hand:\n- ".implode("\n- ", $onHand)
            ."\n\nHousehold diets:\n- ".implode("\n- ", $diets);

        return $this->llm->structured(
            "Suggest {$count} meals for this household, chosen ONLY from their recipe list. "
                .'Bias strongly toward meals that use ingredients already on hand (to cut waste), and '
                .'respect every member\'s diet — on a meat night the excluding member needs a substitute, '
                .'on a fish night the dish is shared. Set uses_stock true when the meal uses on-hand items.',
            $context,
            self::suggestionsSchema(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function suggestionsSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'suggestions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'recipe_name' => ['type' => 'string'],
                            'reason' => ['type' => 'string'],
                            'uses_stock' => ['type' => 'boolean'],
                        ],
                        'required' => ['recipe_name', 'reason', 'uses_stock'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['suggestions'],
            'additionalProperties' => false,
        ];
    }
}
