<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recipe\StoreRecipeRequest;
use App\Http\Requests\Recipe\UpdateRecipeRequest;
use App\Http\Resources\IngredientResource;
use App\Http\Resources\TagResource;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Tag;
use App\Services\Recipes\RecipeExpansionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inertia web controller for recipes + the recipe editor. Reuses the API FormRequests,
 * Resources, and RecipeExpansionService — Inertia transport only.
 */
class RecipeController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Recipe::class);

        $recipes = Recipe::with('tags')->withCount('recipeIngredients')->orderBy('name')->get();

        return Inertia::render('Recipes/Index', [
            'recipes' => $recipes->map(fn (Recipe $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'servings_default' => $r->servings_default,
                'tags' => TagResource::collection($r->tags)->resolve(),
                'ingredient_count' => $r->recipe_ingredients_count,
                'is_draft' => $r->is_draft,
            ]),
        ]);
    }

    /**
     * Confirm an AI-imported draft after review (indication: ai-imports-need-review-screen).
     */
    public function confirm(Recipe $recipe): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $recipe->update(['is_draft' => false]);

        return back()->with('success', 'Recipe confirmed.');
    }

    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        $this->authorize('create', Recipe::class);

        $recipe = Recipe::create($request->validated());

        return redirect("/recipes/{$recipe->id}/edit")->with('success', 'Recipe created.');
    }

    public function edit(Recipe $recipe): Response
    {
        $this->authorize('view', $recipe);

        $recipe->load(['recipeIngredients.ingredient', 'tags', 'subRecipes']);

        return Inertia::render('Recipes/Edit', [
            'recipe' => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'servings_default' => $recipe->servings_default,
                'instructions' => $recipe->instructions,
                'source_type' => $recipe->source_type->value,
                'is_draft' => $recipe->is_draft,
            ],
            'lines' => $recipe->recipeIngredients->map(fn ($ri) => [
                'id' => $ri->id,
                'ingredient_id' => $ri->ingredient_id,
                'name' => $ri->ingredient?->name,
                'diet_class' => $ri->ingredient?->diet_class->value,
                'quantity_hint' => $ri->quantity_hint,
                'note' => $ri->note,
                'is_optional' => $ri->is_optional,
            ])->values(),
            'tags' => TagResource::collection($recipe->tags)->resolve(),
            'subRecipes' => $recipe->subRecipes->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
            'expanded' => app(RecipeExpansionService::class)->expand($recipe)->map(fn ($line) => [
                'ingredient_id' => $line['ingredient_id'],
                'name' => $line['ingredient']?->name,
                'quantity_hint' => $line['quantity_hint'],
            ])->values(),
            'allIngredients' => IngredientResource::collection(Ingredient::orderBy('name')->get())->resolve(),
            'allTags' => TagResource::collection(Tag::orderBy('name')->get())->resolve(),
            'allRecipes' => Recipe::where('id', '!=', $recipe->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $recipe->update($request->validated());

        return back()->with('success', 'Recipe saved.');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $this->authorize('delete', $recipe);

        $recipe->delete();

        return redirect('/recipes')->with('success', 'Recipe deleted.');
    }

    public function addIngredient(Request $request, Recipe $recipe): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $data = $request->validate([
            'ingredient_id' => ['required', Rule::exists('ingredients', 'id')->where('household_id', $request->user()->household_id)],
            'quantity_hint' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'is_optional' => ['boolean'],
        ]);

        $recipe->recipeIngredients()->create($data);

        return back()->with('success', 'Ingredient added.');
    }

    public function removeIngredient(Recipe $recipe, int $recipeIngredient): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $recipe->recipeIngredients()->whereKey($recipeIngredient)->delete();

        return back()->with('success', 'Ingredient removed.');
    }

    public function attachTag(Request $request, Recipe $recipe): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $data = $request->validate([
            'tag_id' => ['required', Rule::exists('tags', 'id')->where('household_id', $request->user()->household_id)],
        ]);

        $recipe->tags()->syncWithoutDetaching([$data['tag_id']]);

        return back();
    }

    public function detachTag(Recipe $recipe, Tag $tag): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $recipe->tags()->detach($tag->id);

        return back();
    }

    public function addComponent(Request $request, Recipe $recipe): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $data = $request->validate([
            'child_recipe_id' => ['required', Rule::exists('recipes', 'id')->where('household_id', $request->user()->household_id)],
        ]);

        $child = Recipe::findOrFail($data['child_recipe_id']);

        if (app(RecipeExpansionService::class)->wouldCreateCycle($recipe, $child)) {
            throw ValidationException::withMessages([
                'child_recipe_id' => 'Linking this recipe would create a circular sub-recipe reference.',
            ]);
        }

        $recipe->subRecipes()->syncWithoutDetaching([$child->id]);

        return back()->with('success', 'Sub-recipe linked.');
    }

    public function removeComponent(Recipe $recipe, Recipe $child): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $recipe->subRecipes()->detach($child->id);

        return back();
    }
}
