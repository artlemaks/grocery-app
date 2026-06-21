<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\IndexInventoryItemRequest;
use App\Http\Requests\Inventory\LogUsageRequest;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use App\Services\Inventory\InventoryDepletionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryItemController extends Controller
{
    public function index(IndexInventoryItemRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InventoryItem::class);

        $items = InventoryItem::query()
            ->when($request->validated('location'), fn ($q, $location) => $q->where('location', $location))
            ->when($request->validated('status'), fn ($q, $status) => $q->where('status', $status))
            ->get();

        return InventoryItemResource::collection($items);
    }

    public function usage(LogUsageRequest $request, InventoryItem $inventoryItem): InventoryItemResource
    {
        $this->authorize('update', $inventoryItem);

        app(InventoryDepletionService::class)->logUsage(
            $inventoryItem,
            (float) $request->validated('amount'),
            $request->validated('meal_plan_entry_id'),
        );

        return new InventoryItemResource($inventoryItem->fresh()->load('ingredient'));
    }
}
