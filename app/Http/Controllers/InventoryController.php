<?php

namespace App\Http\Controllers;

use App\Models\InventoryAdjustment;
use App\Models\Warehouse;
use App\Models\WarehouseStockable;
use App\Models\WarehouseStockableTrackingNumber;
use App\Models\WarehouseStockableTrackingNumberOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * GET /api/inventory/{warehouse}/articles
     * Returns all articles in a warehouse with tracking numbers and remaining quantities.
     */
    public function articles(Warehouse $warehouse): JsonResponse
    {
        $warehouse->load([
            'warehouse_stockables.stockable',
            'warehouse_stockables.warehouse_stockable_tracking_numbers.warehouse_stockable_tracking_number_operations',
        ]);

        $articles = [];

        foreach ($warehouse->warehouse_stockables as $stockable) {
            $articleModel = $stockable->stockable;
            if (!$articleModel) continue;

            $stockableType = class_basename($stockable->stockable_type);

            $articleName = $articleModel->name_fr ?? $articleModel->name_en ?? $articleModel->name_es ?? 'Unknown';

            $trackingNumbers = [];

            foreach ($stockable->warehouse_stockable_tracking_numbers as $tn) {
                $balance = $this->calculateBalance($tn->warehouse_stockable_tracking_number_operations);

                $trackingNumbers[] = [
                    'id'               => $tn->id,
                    'tracking_numbers' => $tn->tracking_numbers,
                    'date_in'          => $tn->date_in_warehouse,
                    'date_out'         => $tn->date_out_of_warehouse,
                    'weight'           => round($balance['weight'], 2),
                    'price'            => round($balance['price'], 2),
                    'packages'         => round($balance['packages'], 2),
                    'custom_wap'       => $tn->custom_wap,
                ];
            }

            $articles[] = [
                'stockable_id'   => $stockable->id,
                'stockable_type' => $stockableType,
                'article_id'     => $stockable->stockable_id,
                'article_name'   => $articleName,
                'article_names'  => [
                    'fr' => $articleModel->name_fr ?? null,
                    'en' => $articleModel->name_en ?? null,
                    'es' => $articleModel->name_es ?? null,
                ],
                'tracking_numbers' => $trackingNumbers,
                'total_weight' => round(collect($trackingNumbers)->sum('weight'), 2),
            ];
        }

        // Sort: articles with stock first
        usort($articles, fn($a, $b) => $b['total_weight'] <=> $a['total_weight']);

        return response()->json(['articles' => $articles], 200);
    }

    /**
     * POST /api/inventory/adjust
     * Batch adjust quantities for tracking numbers.
     *
     * Body: {
     *   warehouse_id: int,
     *   reason: string|null,
     *   date: string (Y-m-d),
     *   adjustments: [
     *     { tracking_number_id: int, new_weight: float, new_price: float|null, new_packages: float|null }
     *   ]
     * }
     */
    public function adjust(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id'                     => 'required|exists:warehouses,id',
            'date'                             => 'required|date',
            'adjustments'                      => 'required|array|min:1',
            'adjustments.*.tracking_number_id' => 'required|exists:warehouse_stockable_tracking_numbers,id',
            'adjustments.*.new_weight'         => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $warehouse = Warehouse::findOrFail($request->warehouse_id);

            // Create the adjustment record
            $adjustment = InventoryAdjustment::create([
                'warehouse_id' => $warehouse->id,
                'user_id'      => auth()->id(),
                'reason'       => $request->reason,
                'date'         => $request->date,
            ]);

            $changesCount = 0;

            foreach ($request->adjustments as $adj) {
                $tn = WarehouseStockableTrackingNumber::with('warehouse_stockable_tracking_number_operations')
                    ->findOrFail($adj['tracking_number_id']);

                $currentBalance = $this->calculateBalance($tn->warehouse_stockable_tracking_number_operations);
                $currentWeight  = round($currentBalance['weight'], 2);
                $newWeight      = round($adj['new_weight'], 2);
                $diff           = round($newWeight - $currentWeight, 2);

                if ($diff == 0) continue;

                $changesCount++;

                // Determine price for this operation
                $opPrice = 0;
                if (isset($adj['new_price']) && $adj['new_price'] !== null) {
                    $opPrice = abs($adj['new_price']);
                } else {
                    // Use WAP to estimate price
                    $wap = $this->calculateWAP($tn);
                    if ($wap !== null) {
                        $opPrice = round(abs($diff) * $wap, 2);
                    }
                }

                $opPackages = $adj['new_packages'] ?? 0;

                // Get stockable info from an existing operation
                $sampleOp = $tn->warehouse_stockable_tracking_number_operations->first();
                $stockableType = $sampleOp?->stockable_type;
                $stockableId   = $sampleOp?->stockable_id;
                $wcoId         = $sampleOp?->warehouse_category_operation_id;
                $packageTypeId = $sampleOp?->package_type_id;

                WarehouseStockableTrackingNumberOperation::create([
                    'type_operation'                          => $diff > 0 ? 'in' : 'out',
                    'date'                                    => $request->date,
                    'total_weight'                            => abs($diff),
                    'total_price'                             => $opPrice,
                    'total_package_count'                     => abs($opPackages),
                    'unit_price'                              => abs($diff) > 0 ? ($opPrice / abs($diff)) : 0,
                    'warehouse_stockable_tracking_number_id'  => $tn->id,
                    'warehouse_category_operation_id'         => $wcoId,
                    'stockable_type'                          => $stockableType,
                    'stockable_id'                            => $stockableId,
                    'package_type_id'                         => $packageTypeId,
                    'sourceable_type'                         => InventoryAdjustment::class,
                    'sourceable_id'                           => $adjustment->id,
                ]);

                // Cascade recalculation
                $tn->calc();
            }

            if ($changesCount === 0) {
                DB::rollBack();
                return response()->json(['message' => 'No changes to apply.'], 422);
            }

            // Recalculate warehouse stockable and warehouse totals
            $warehouse->load('warehouse_stockables.warehouse_stockable_tracking_numbers.warehouse_stockable_tracking_number_operations');
            foreach ($warehouse->warehouse_stockables as $ws) {
                $ws->calc();
            }
            $warehouse->calc();

            DB::commit();

            return response()->json([
                'message'     => 'Inventory adjusted successfully.',
                'changes'     => $changesCount,
                'adjustment'  => $adjustment,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Adjustment failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/inventory/add-tracking
     * Add a new tracking number to a warehouse article.
     *
     * Body: {
     *   warehouse_id: int,
     *   stockable_id: int (warehouse_stockable_id),
     *   tracking_numbers: array,
     *   weight: float,
     *   price: float|null,
     *   packages: float,
     *   date: string,
     *   reason: string|null
     * }
     */
    public function addTracking(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id'     => 'required|exists:warehouses,id',
            'stockable_id'     => 'required|exists:warehouse_stockables,id',
            'tracking_numbers' => 'required|array|min:1',
            'weight'           => 'required|numeric|min:0.01',
            'date'             => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $warehouse = Warehouse::findOrFail($request->warehouse_id);
            $ws = WarehouseStockable::findOrFail($request->stockable_id);

            // Create adjustment record
            $adjustment = InventoryAdjustment::create([
                'warehouse_id' => $warehouse->id,
                'user_id'      => auth()->id(),
                'reason'       => $request->reason ?? 'Added tracking number via inventory',
                'date'         => $request->date,
            ]);

            // Create the tracking number
            $tn = WarehouseStockableTrackingNumber::create([
                'warehouse_stockable_id' => $ws->id,
                'tracking_numbers'       => $request->tracking_numbers,
                'date_in_warehouse'      => $request->date,
            ]);

            // Get stockable info from existing operations or from the warehouse stockable
            $sampleOp = $ws->warehouse_stockable_tracking_numbers()
                ->first()
                ?->warehouse_stockable_tracking_number_operations()
                ->first();

            $price = $request->price ?? 0;
            $packages = $request->packages ?? 0;

            WarehouseStockableTrackingNumberOperation::create([
                'type_operation'                          => 'in',
                'date'                                    => $request->date,
                'total_weight'                            => $request->weight,
                'total_price'                             => $price,
                'total_package_count'                     => $packages,
                'unit_price'                              => $request->weight > 0 ? ($price / $request->weight) : 0,
                'warehouse_stockable_tracking_number_id'  => $tn->id,
                'warehouse_category_operation_id'         => $sampleOp?->warehouse_category_operation_id,
                'stockable_type'                          => $ws->stockable_type,
                'stockable_id'                            => $ws->stockable_id,
                'package_type_id'                         => $request->package_type_id ?? $sampleOp?->package_type_id,
                'sourceable_type'                         => InventoryAdjustment::class,
                'sourceable_id'                           => $adjustment->id,
            ]);

            // Cascade
            $tn->calc();
            $ws->load('warehouse_stockable_tracking_numbers.warehouse_stockable_tracking_number_operations');
            $ws->calc();
            $warehouse->load('warehouse_stockables.warehouse_stockable_tracking_numbers.warehouse_stockable_tracking_number_operations');
            $warehouse->calc();

            DB::commit();

            return response()->json(['message' => 'Tracking number added.', 'tracking_number' => $tn], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/inventory/tracking/{tn}
     * Soft-delete a tracking number and its operations, recalculate.
     */
    public function deleteTracking(WarehouseStockableTrackingNumber $tn): JsonResponse
    {
        DB::beginTransaction();
        try {
            $ws = $tn->warehouse_stockable;
            $warehouse = $ws->warehouse;

            // Soft delete all operations
            $tn->warehouse_stockable_tracking_number_operations()->delete();
            $tn->delete();

            // Cascade recalculate
            $ws->load('warehouse_stockable_tracking_numbers.warehouse_stockable_tracking_number_operations');
            $ws->calc();
            $warehouse->load('warehouse_stockables.warehouse_stockable_tracking_numbers.warehouse_stockable_tracking_number_operations');
            $warehouse->calc();

            DB::commit();

            return response()->json(['message' => 'Tracking number deleted.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/inventory/add-article
     * Add one or more articles (FishType/GuanoType/PackageType) to a warehouse.
     *
     * Body: {
     *   warehouse_id: int,
     *   articles: [
     *     { stockable_type: 'FishType'|'GuanoType'|'PackageType', stockable_id: int }
     *   ]
     * }
     */
    public function addArticle(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id'                => 'required|exists:warehouses,id',
            'articles'                    => 'required|array|min:1',
            'articles.*.stockable_type'   => 'required|in:FishType,GuanoType,PackageType',
            'articles.*.stockable_id'     => 'required|integer',
        ]);

        $morphMap = [
            'FishType'    => \App\Models\FishType::class,
            'GuanoType'   => \App\Models\GuanoType::class,
            'PackageType' => \App\Models\PackageType::class,
        ];

        $added = 0;
        $skipped = 0;

        foreach ($request->articles as $art) {
            $stockableClass = $morphMap[$art['stockable_type']];

            // Verify the article exists
            $stockableClass::findOrFail($art['stockable_id']);

            // Skip if already exists in warehouse
            $exists = WarehouseStockable::where('warehouse_id', $request->warehouse_id)
                ->where('stockable_type', $art['stockable_type'])
                ->where('stockable_id', $art['stockable_id'])
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            WarehouseStockable::create([
                'warehouse_id'        => $request->warehouse_id,
                'stockable_type'      => $art['stockable_type'],
                'stockable_id'        => $art['stockable_id'],
                'unit_price'          => 0,
                'total_weight'        => 0,
                'total_price'         => 0,
                'total_package_count' => 0,
            ]);

            $added++;
        }

        if ($added === 0) {
            return response()->json(['message' => 'No new articles to add (all already exist).'], 422);
        }

        return response()->json([
            'message' => "$added article(s) added to warehouse." . ($skipped > 0 ? " $skipped skipped (already exist)." : ''),
            'added'   => $added,
            'skipped' => $skipped,
        ], 201);
    }

    // ─── Helpers ───

    private function calculateBalance($operations): array
    {
        $weight = 0;
        $price = 0;
        $packages = 0;

        foreach ($operations as $op) {
            if ($op->type_operation === 'in') {
                $weight   += $op->total_weight;
                $price    += $op->total_price;
                $packages += $op->total_package_count;
            } else {
                $weight   -= $op->total_weight;
                $price    -= $op->total_price;
                $packages -= $op->total_package_count;
            }
        }

        return compact('weight', 'price', 'packages');
    }

    private function calculateWAP(WarehouseStockableTrackingNumber $tn): ?float
    {
        if ($tn->custom_wap !== null) {
            return (float) $tn->custom_wap;
        }

        $outOps = $tn->warehouse_stockable_tracking_number_operations
            ->where('type_operation', 'out')
            ->sortByDesc('date')
            ->take(4);

        if ($outOps->isEmpty()) {
            // Fallback to IN operations average
            $inOps = $tn->warehouse_stockable_tracking_number_operations
                ->where('type_operation', 'in');

            if ($inOps->isEmpty()) return null;

            $totalWeight = $inOps->sum('total_weight');
            $totalPrice  = $inOps->sum('total_price');
            return $totalWeight > 0 ? ($totalPrice / $totalWeight) : null;
        }

        $totalWeight = $outOps->sum('total_weight');
        $totalPrice  = $outOps->sum('total_price');

        return $totalWeight > 0 ? ($totalPrice / $totalWeight) : null;
    }

    // ─── Adjustment History ───

    /**
     * GET /api/inventory/adjustments
     * Returns paginated list of all inventory adjustments.
     */
    public function adjustmentHistory(Request $request): JsonResponse
    {
        $query = InventoryAdjustment::with(['warehouse:id,name', 'user:id,name'])
            ->withCount('warehouse_stockable_tracking_number_operations');

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        // Search by reason
        if ($request->filled('search')) {
            $query->where('reason', 'like', '%' . $request->search . '%');
        }

        // Get operations summary for each adjustment
        $adjustments = $query->latest()->paginate($request->get('per_page', 15));

        // Enrich each adjustment with weight summary
        $adjustments->getCollection()->transform(function ($adj) {
            $ops = WarehouseStockableTrackingNumberOperation::where('sourceable_type', 'InventoryAdjustment')
                ->where('sourceable_id', $adj->id)
                ->get();

            $totalIn  = $ops->where('type_operation', 'in')->sum('total_weight');
            $totalOut = $ops->where('type_operation', 'out')->sum('total_weight');

            $adj->total_weight_in  = round($totalIn, 2);
            $adj->total_weight_out = round($totalOut, 2);
            $adj->net_weight_change = round($totalIn - $totalOut, 2);

            return $adj;
        });

        return response()->json($adjustments);
    }

    /**
     * GET /api/inventory/adjustments/{id}
     * Returns single adjustment with all detailed operations.
     */
    public function adjustmentDetail($id): JsonResponse
    {
        $adjustment = InventoryAdjustment::with(['warehouse:id,name', 'user:id,name'])
            ->findOrFail($id);

        $operations = WarehouseStockableTrackingNumberOperation::with([
                'warehouse_stockable_tracking_number:id,warehouse_stockable_id',
                'warehouse_stockable_tracking_number.warehouse_stockable:id,warehouse_id,stockable_type,stockable_id',
                'warehouse_stockable_tracking_number.warehouse_stockable.stockable',
                'stockable',
            ])
            ->where('sourceable_type', 'InventoryAdjustment')
            ->where('sourceable_id', $adjustment->id)
            ->get()
            ->map(function ($op) {
                $tn = $op->warehouse_stockable_tracking_number;
                $ws = $tn?->warehouse_stockable;
                $stockable = $ws?->stockable ?? $op->stockable;

                return [
                    'id'               => $op->id,
                    'type_operation'   => $op->type_operation,
                    'date'             => $op->date,
                    'total_weight'     => $op->total_weight,
                    'total_price'      => $op->total_price,
                    'unit_price'       => $op->unit_price,
                    'total_package_count' => $op->total_package_count,
                    'tracking_number_id' => $tn?->id,
                    'tracking_numbers' => $tn?->tracking_numbers ?? [],
                    'article_type'     => $ws?->stockable_type,
                    'article_name'     => $stockable?->name_fr ?? $stockable?->name ?? 'N/A',
                    'article_name_en'  => $stockable?->name_en ?? $stockable?->name_fr ?? 'N/A',
                    'article_name_es'  => $stockable?->name_es ?? $stockable?->name_fr ?? 'N/A',
                ];
            });

        return response()->json([
            'adjustment' => $adjustment,
            'operations' => $operations,
        ]);
    }

    /**
     * PUT /api/inventory/adjustments/{id}
     * Update adjustment metadata (date, reason).
     */
    public function updateAdjustment(Request $request, $id): JsonResponse
    {
        $request->validate([
            'date'   => 'sometimes|date',
            'reason' => 'nullable|string|max:1000',
        ]);

        $adjustment = InventoryAdjustment::findOrFail($id);

        DB::beginTransaction();
        try {
            $oldDate = $adjustment->date;
            $newDate = $request->has('date') ? $request->date : $adjustment->date;

            $adjustment->update([
                'date'   => $newDate,
                'reason' => $request->has('reason') ? $request->reason : $adjustment->reason,
            ]);

            // If date changed, update all linked operations' date too
            if ($request->has('date') && $newDate != $oldDate) {
                WarehouseStockableTrackingNumberOperation::where('sourceable_type', 'InventoryAdjustment')
                    ->where('sourceable_id', $adjustment->id)
                    ->update(['date' => $newDate]);
            }

            DB::commit();

            return response()->json([
                'message'    => 'Adjustment updated successfully.',
                'adjustment' => $adjustment->fresh(['warehouse:id,name', 'user:id,name']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update: ' . $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/inventory/adjustments/{id}
     * Reverse all operations and delete the adjustment.
     */
    public function deleteAdjustment($id): JsonResponse
    {
        $adjustment = InventoryAdjustment::findOrFail($id);

        DB::beginTransaction();
        try {
            // Get all operations linked to this adjustment
            $operations = WarehouseStockableTrackingNumberOperation::where('sourceable_type', 'InventoryAdjustment')
                ->where('sourceable_id', $adjustment->id)
                ->get();

            // Collect unique tracking number IDs and warehouse IDs for recalculation
            $tnIds = [];
            $warehouseIds = [];

            foreach ($operations as $op) {
                $tnIds[] = $op->warehouse_stockable_tracking_number_id;
                // Soft-delete the operation
                $op->delete();
            }

            $tnIds = array_unique(array_filter($tnIds));

            // Recalculate each affected tracking number
            foreach ($tnIds as $tnId) {
                $tn = WarehouseStockableTrackingNumber::with([
                    'warehouse_stockable_tracking_number_operations',
                    'warehouse_stockable',
                ])->find($tnId);

                if ($tn) {
                    $tn->calc();
                    if ($tn->warehouse_stockable) {
                        $tn->warehouse_stockable->calc();
                        $warehouseIds[] = $tn->warehouse_stockable->warehouse_id;
                    }
                }
            }

            // Recalculate affected warehouses
            $warehouseIds = array_unique(array_filter($warehouseIds));
            foreach ($warehouseIds as $whId) {
                $wh = Warehouse::find($whId);
                if ($wh) $wh->calc();
            }

            // Soft-delete the adjustment
            $adjustment->delete();

            DB::commit();

            return response()->json([
                'message' => 'Adjustment deleted and stock recalculated.',
                'reversed_operations' => count($operations),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete: ' . $e->getMessage()], 500);
        }
    }
}
