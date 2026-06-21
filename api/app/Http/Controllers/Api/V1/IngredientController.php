<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ingredient\SetSubstituteRequest;
use App\Http\Requests\Ingredient\StoreIngredientRequest;
use App\Http\Requests\Ingredient\UpdateIngredientRequest;
use App\Http\Resources\IngredientResource;
use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class IngredientController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Ingredient::class);

        return IngredientResource::collection(
            Ingredient::query()->orderBy('name')->get()
        );
    }

    public function store(StoreIngredientRequest $request): JsonResponse
    {
        $this->authorize('create', Ingredient::class);

        $ingredient = Ingredient::create($request->validated());

        return (new IngredientResource($ingredient))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Ingredient $ingredient): IngredientResource
    {
        $this->authorize('view', $ingredient);

        return new IngredientResource($ingredient);
    }

    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): IngredientResource
    {
        $this->authorize('update', $ingredient);

        $ingredient->update($request->validated());

        return new IngredientResource($ingredient);
    }

    public function destroy(Ingredient $ingredient): Response
    {
        $this->authorize('delete', $ingredient);

        $ingredient->delete();

        return response()->noContent();
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Ingredient::class);

        $q = (string) $request->query('q', '');

        // Postgres (production) has a native case-insensitive ILIKE. SQLite (tests)
        // does not parse ILIKE, but its LIKE is already case-insensitive for ASCII.
        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $ingredients = Ingredient::query()
            ->where('name', $operator, '%'.$q.'%')
            ->limit(20)
            ->get();

        return IngredientResource::collection($ingredients);
    }

    public function substitute(SetSubstituteRequest $request, Ingredient $ingredient): IngredientResource
    {
        $this->authorize('update', $ingredient);

        $ingredient->substitute_ingredient_id = $request->validated('substitute_ingredient_id');
        $ingredient->save();

        return new IngredientResource($ingredient);
    }
}
