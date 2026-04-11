<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseStockableTrackingNumber;
use App\Models\WarehouseStockableTrackingNumberOperation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    /**
     * GET /api/warehouses
     * All warehouses with aggregated totals.
     */
    public function index(): JsonResponse
    {
        $warehouses = Warehouse::with('warehouse_category')
            ->withCount('warehouse_stockables')
            ->get()
            ->map(function ($w) {
                return [
                    'id'                    => $w->id,
                    'name'                  => $w->name,
                    'description'           => $w->description,
                    'warehouse_type'        => $w->warehouse_type,
                    'category'              => $w->warehouse_category?->name,
                    'warehouse_category_id' => $w->warehouse_category_id,
                    'total_weight'          => round($w->total_weight, 2),
                    'total_price'           => round($w->total_price, 2),
                    'total_package_count'   => round($w->total_package_count, 2),
                    'unit_price'            => round($w->unit_price, 2),
                    'article_count'         => $w->warehouse_stockables_count,
                    'has_sejour'            => $w->has_sejour,
                    'operation_service_fee' => round($w->operation_service_fee, 2),
                    'created_at'            => $w->created_at?->toDateString(),
                ];
            });

        // Also return unique warehouse categories
        $categories = \App\Models\WarehouseCategory::select('id', 'name')->get();

        return response()->json(['warehouses' => $warehouses, 'categories' => $categories], 200);
    }

    /**
     * GET /api/warehouses/global-search?q=searchterm
     * Search articles across all warehouses by tracking number or article name.
     */
    public function globalSearch(Request $request): JsonResponse
    {
        $q = $request->query('q', '');
        if (strlen($q) < 2) {
            return response()->json(['results' => []], 200);
        }

        $results = [];

        $warehouses = Warehouse::with([
            'warehouse_stockables.stockable',
            'warehouse_stockables.warehouse_stockable_tracking_numbers.warehouse_stockable_tracking_number_operations.sourceable',
        ])->get();

        foreach ($warehouses as $warehouse) {
            foreach ($warehouse->warehouse_stockables as $ws) {
                $articleModel = $ws->stockable;
                if (!$articleModel) continue;

                $stockableType = class_basename($ws->stockable_type);
                $groupInfo = $this->resolveGroupAndName($articleModel, $stockableType);
                $articleName = $groupInfo['article_name'];

                // Check if article name or group name matches
                $articleNameStr = implode(' ', array_filter(array_values($articleName)));
                $groupName = $groupInfo['group_name'];
                $articleMatches = stripos($articleNameStr, $q) !== false || stripos($groupName, $q) !== false;

                foreach ($ws->warehouse_stockable_tracking_numbers as $tn) {
                    // Check tracking numbers
                    $tnStr = is_array($tn->tracking_numbers) ? implode(' ', $tn->tracking_numbers) : '';
                    $tnMatches = stripos($tnStr, $q) !== false;

                    if (!$articleMatches && !$tnMatches) continue;

                    $balance = $this->calculateBalance($tn->warehouse_stockable_tracking_number_operations);
                    $wap = $this->calculateWAP($tn);
                    $remainingWeight = round(max($balance['weight'], 0), 2);

                    // Get supplier from first IN op
                    $supplierName = null;
                    foreach ($tn->warehouse_stockable_tracking_number_operations as $op) {
                        if ($op->type_operation === 'in' && $op->sourceable) {
                            $source = $op->sourceable;
                            if ($source instanceof \App\Models\Charge) {
                                $source->loadMissing('suppliers');
                                $supplierName = $source->suppliers->pluck('name')->implode(', ');
                            }
                            break;
                        }
                    }

                    $results[] = [
                        'warehouse_id'       => $warehouse->id,
                        'warehouse_name'     => $warehouse->name,
                        'tracking_number_id' => $tn->id,
                        'tracking_numbers'   => $tn->tracking_numbers,
                        'article_name'       => $articleName,
                        'group_name'         => $groupInfo['group_name'],
                        'stockable_type'     => $stockableType,
                        'date_entered'       => $tn->date_in_warehouse,
                        'supplier_name'      => $supplierName,
                        'remaining_weight'   => $remainingWeight,
                        'nbr_boxes'          => $stockableType === 'FishType' ? round($balance['packages'], 0) : null,
                        'wap'                => $wap !== null ? round($wap, 2) : null,
                        'total_price'        => $wap !== null ? round($wap * $remainingWeight, 2) : null,
                    ];
                }
            }
        }

        return response()->json(['results' => $results], 200);
    }

    /**
     * GET /api/warehouses/dashboard-stats?period=daily|monthly|yearly&date_start=&date_end=
     * Global stats for line chart: stock-in vs sold-out over time.
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $period    = $request->query('period', 'daily');
        $defaultStart = match ($period) {
            'yearly'  => Carbon::now()->subYears(5)->toDateString(),
            'monthly' => Carbon::now()->subYears(2)->toDateString(),
            default   => Carbon::now()->subMonths(6)->toDateString(),
        };
        $dateStart = $request->query('date_start', $defaultStart);
        $dateEnd   = $request->query('date_end', Carbon::now()->toDateString());

        // Date format for grouping
        $dateFormat = match ($period) {
            'monthly' => '%Y-%m',
            'yearly'  => '%Y',
            default   => '%Y-%m-%d',
        };

        // Stock IN (from charges)
        $stockIn = WarehouseStockableTrackingNumberOperation::where('type_operation', 'in')
            ->whereBetween('date', [$dateStart, $dateEnd])
            ->whereNull('deleted_at')
            ->select(
                DB::raw("DATE_FORMAT(date, '{$dateFormat}') as period_label"),
                DB::raw('SUM(total_weight) as total_weight'),
                DB::raw('SUM(total_price) as total_price'),
                DB::raw('COUNT(*) as operations_count')
            )
            ->groupBy('period_label')
            ->orderBy('period_label')
            ->get()
            ->keyBy('period_label');

        // Sold OUT (from sells)
        $soldOut = WarehouseStockableTrackingNumberOperation::where('type_operation', 'out')
            ->whereBetween('date', [$dateStart, $dateEnd])
            ->whereNull('deleted_at')
            ->select(
                DB::raw("DATE_FORMAT(date, '{$dateFormat}') as period_label"),
                DB::raw('SUM(total_weight) as total_weight'),
                DB::raw('SUM(total_price) as total_price'),
                DB::raw('COUNT(*) as operations_count')
            )
            ->groupBy('period_label')
            ->orderBy('period_label')
            ->get()
            ->keyBy('period_label');

        // Merge all period labels
        $allLabels = $stockIn->keys()->merge($soldOut->keys())->unique()->sort()->values();

        $chartData = $allLabels->map(function ($label) use ($stockIn, $soldOut) {
            return [
                'label'            => $label,
                'stock_in_weight'  => round($stockIn->get($label)?->total_weight ?? 0, 2),
                'stock_in_price'   => round($stockIn->get($label)?->total_price ?? 0, 2),
                'sold_out_weight'  => round($soldOut->get($label)?->total_weight ?? 0, 2),
                'sold_out_price'   => round($soldOut->get($label)?->total_price ?? 0, 2),
            ];
        });

        // Summary KPIs — Global
        $totalWarehouses = Warehouse::count();
        $totalStockWeight = Warehouse::sum('total_weight');
        $totalStockValue  = Warehouse::sum('total_price');
        $totalArticles    = DB::table('warehouse_stockables')->whereNull('deleted_at')->count();

        // Per-category KPIs
        $categories = \App\Models\WarehouseCategory::select('id', 'name')->get();
        $categoryKpis = [];

        foreach ($categories as $cat) {
            $catWarehouses = Warehouse::where('warehouse_category_id', $cat->id);
            $warehouseIds = (clone $catWarehouses)->pluck('id');

            $categoryKpis[] = [
                'id'                 => $cat->id,
                'name'               => $cat->name,
                'total_warehouses'   => $catWarehouses->count(),
                'total_stock_weight' => round((clone $catWarehouses)->sum('total_weight'), 2),
                'total_stock_value'  => round((clone $catWarehouses)->sum('total_price'), 2),
                'total_articles'     => DB::table('warehouse_stockables')
                    ->whereIn('warehouse_id', $warehouseIds)
                    ->whereNull('deleted_at')
                    ->count(),
            ];
        }

        return response()->json([
            'chart'    => $chartData,
            'kpis'     => [
                'total_warehouses'   => $totalWarehouses,
                'total_stock_weight' => round($totalStockWeight, 2),
                'total_stock_value'  => round($totalStockValue, 2),
                'total_articles'     => $totalArticles,
            ],
            'category_kpis' => $categoryKpis,
        ], 200);
    }

    /**
     * GET /api/warehouses/{warehouse}/stock-details
     * Nested data: articles grouped by family, each with tracking numbers.
     */
    public function stockDetails(Warehouse $warehouse): JsonResponse
    {
        $warehouse->load([
            'warehouse_stockables.stockable',
            'warehouse_stockables.warehouse_stockable_tracking_numbers.warehouse_stockable_tracking_number_operations' => function ($q) {
                $q->with([
                    'sourceable',
                    'warehouse_category_operation',
                    'package_type',
                ]);
            },
        ]);

        $families = [];

        foreach ($warehouse->warehouse_stockables as $stockable) {
            $articleModel = $stockable->stockable;
            if (!$articleModel) continue;

            $stockableType = class_basename($stockable->stockable_type);
            $groupInfo = $this->resolveGroupAndName($articleModel, $stockableType);
            $groupKey  = $groupInfo['group_name'];

            if (!isset($families[$groupKey])) {
                $families[$groupKey] = [
                    'group_name'   => $groupKey,
                    'group_type'   => $stockableType,
                    'total_weight' => 0,
                    'total_price'  => 0,
                    'articles'     => [],
                ];
            }

            foreach ($stockable->warehouse_stockable_tracking_numbers as $tn) {
                $balance = $this->calculateBalance($tn->warehouse_stockable_tracking_number_operations);
                $wap = $this->calculateWAP($tn);
                $remainingWeight = round(max($balance['weight'], 0), 2);
                $totalPrice = $wap !== null ? round($wap * $remainingWeight, 2) : null;

                // Get supplier name from first IN operation's source charge
                $supplierName = null;
                $dateEntered  = $tn->date_in_warehouse;
                foreach ($tn->warehouse_stockable_tracking_number_operations as $op) {
                    if ($op->type_operation === 'in' && $op->sourceable) {
                        $source = $op->sourceable;
                        if ($source instanceof \App\Models\Charge) {
                            $source->loadMissing('suppliers');
                            $supplierName = $source->suppliers->pluck('name')->implode(', ');
                        }
                        break;
                    }
                }

                $families[$groupKey]['articles'][] = [
                    'tracking_number_id' => $tn->id,
                    'tracking_numbers'   => $tn->tracking_numbers,
                    'article_name'       => $groupInfo['article_name'],
                    'stockable_type'     => $stockableType,
                    'stockable_id'       => $stockable->stockable_id,
                    'date_entered'       => $dateEntered,
                    'supplier_name'      => $supplierName,
                    'nbr_boxes'          => $stockableType === 'FishType' ? round($balance['packages'], 0) : null,
                    'remaining_weight'   => $remainingWeight,
                    'wap'                => $wap !== null ? round($wap, 2) : null,
                    'total_price'        => $totalPrice,
                    'custom_wap'         => $tn->custom_wap,
                ];

                $families[$groupKey]['total_weight'] += $remainingWeight;
                $families[$groupKey]['total_price']  += $totalPrice ?? 0;
            }
        }

        // Round family totals
        foreach ($families as &$family) {
            $family['total_weight'] = round($family['total_weight'], 2);
            $family['total_price']  = round($family['total_price'], 2);
        }

        return response()->json([
            'warehouse' => [
                'id'   => $warehouse->id,
                'name' => $warehouse->name,
            ],
            'families' => array_values($families),
        ], 200);
    }

    /**
     * GET /api/warehouses/{warehouse}/tracking-number/{tn}/transactions
     * Transaction history for a tracking number.
     */
    public function trackingNumberTransactions(Warehouse $warehouse, $tnId): JsonResponse
    {
        $tn = WarehouseStockableTrackingNumber::with([
            'warehouse_stockable_tracking_number_operations' => function ($q) {
                $q->with(['sourceable', 'stockable'])->orderBy('date', 'asc');
            },
        ])->findOrFail($tnId);

        $runningWeight = 0;
        $transactions  = [];

        foreach ($tn->warehouse_stockable_tracking_number_operations as $op) {
            if ($op->type_operation === 'in') {
                $runningWeight += $op->total_weight;
            } else {
                $runningWeight -= $op->total_weight;
            }

            $sourceType = $op->sourceable_type ? class_basename($op->sourceable_type) : null;

            $transactions[] = [
                'id'              => $op->id,
                'type'            => $op->type_operation,
                'date'            => $op->date,
                'weight'          => round($op->total_weight, 2),
                'price'           => round($op->total_price, 2),
                'unit_price'      => round($op->unit_price, 2),
                'packages'        => round($op->total_package_count, 2),
                'running_weight'  => round(max($runningWeight, 0), 2),
                'source_type'     => $sourceType,
                'source_id'       => $op->sourceable_id,
            ];
        }

        return response()->json([
            'tracking_number_id' => $tn->id,
            'tracking_numbers'   => $tn->tracking_numbers,
            'transactions'       => $transactions,
        ], 200);
    }

    /**
     * PUT /api/warehouses/tracking-number/{tn}/wap
     * Update manual WAP for a tracking number.
     */
    public function updateWap(Request $request, $tnId): JsonResponse
    {
        $request->validate([
            'custom_wap' => 'required|numeric|min:0',
        ]);

        $tn = WarehouseStockableTrackingNumber::findOrFail($tnId);
        $tn->update(['custom_wap' => $request->custom_wap]);

        return response()->json([
            'message'    => 'WAP updated successfully',
            'custom_wap' => $tn->custom_wap,
        ], 200);
    }

    /**
     * GET /api/warehouses/{warehouse}/pie-chart
     * Article distribution by weight for pie chart.
     */
    public function pieChart(Warehouse $warehouse): JsonResponse
    {
        $warehouse->load([
            'warehouse_stockables.stockable',
            'warehouse_stockables.warehouse_stockable_tracking_numbers.warehouse_stockable_tracking_number_operations',
        ]);

        $distribution = [];
        $totalWeight = 0;

        foreach ($warehouse->warehouse_stockables as $ws) {
            $articleModel = $ws->stockable;
            if (!$articleModel) continue;

            $stockableType = class_basename($ws->stockable_type);
            $groupInfo = $this->resolveGroupAndName($articleModel, $stockableType);

            $weight = 0;
            foreach ($ws->warehouse_stockable_tracking_numbers as $tn) {
                $balance = $this->calculateBalance($tn->warehouse_stockable_tracking_number_operations);
                $weight += max($balance['weight'], 0);
            }

            if ($weight > 0) {
                $articleKey = ($groupInfo['article_name']['name_en'] ?? 'Unknown');
                $distribution[] = [
                    'name'   => $articleKey,
                    'weight' => round($weight, 2),
                ];
                $totalWeight += $weight;
            }
        }

        // Calculate percentages
        foreach ($distribution as &$item) {
            $item['percentage'] = $totalWeight > 0 ? round(($item['weight'] / $totalWeight) * 100, 1) : 0;
        }

        return response()->json([
            'warehouse_name' => $warehouse->name,
            'total_weight'   => round($totalWeight, 2),
            'distribution'   => $distribution,
        ], 200);
    }

    /**
     * GET /api/warehouses/{warehouse}/stock-summary
     * Lightweight stock for AddSell validation (KEPT from previous implementation).
     */
    public function stockSummary(Warehouse $warehouse): JsonResponse
    {
        $warehouse->load([
            'warehouse_stockables.stockable',
            'warehouse_stockables.warehouse_stockable_tracking_numbers.warehouse_stockable_tracking_number_operations',
        ]);

        $stock = [];

        foreach ($warehouse->warehouse_stockables as $ws) {
            $articleModel = $ws->stockable;
            if (!$articleModel) continue;

            $stockableType = class_basename($ws->stockable_type);
            $totalWeight   = 0;
            $totalPackages = 0;

            foreach ($ws->warehouse_stockable_tracking_numbers as $tn) {
                foreach ($tn->warehouse_stockable_tracking_number_operations as $op) {
                    if ($op->type_operation === 'in') {
                        $totalWeight   += $op->total_weight;
                        $totalPackages += $op->total_package_count;
                    } else {
                        $totalWeight   -= $op->total_weight;
                        $totalPackages -= $op->total_package_count;
                    }
                }
            }

            $stock[] = [
                'stockable_type'     => $stockableType,
                'stockable_id'       => $ws->stockable_id,
                'available_weight'   => round(max($totalWeight, 0), 2),
                'available_packages' => round(max($totalPackages, 0), 2),
            ];
        }

        return response()->json(['stock' => $stock], 200);
    }

    // ─────────────────────────────────────────
    //  Private Helpers
    // ─────────────────────────────────────────

    private function resolveGroupAndName($articleModel, string $stockableType): array
    {
        $articleName = [
            'name_fr' => $articleModel->name_fr ?? null,
            'name_en' => $articleModel->name_en ?? null,
            'name_es' => $articleModel->name_es ?? null,
        ];

        $groupName = $stockableType;

        if ($stockableType === 'FishType') {
            $family = $articleModel->fish_family;
            $groupName = $family
                ? ($family->name_en ?? $family->name_fr ?? 'Unknown Family')
                : 'No Family';
        } elseif ($stockableType === 'GuanoType') {
            $groupName = 'Guano';
        } elseif ($stockableType === 'PackageType') {
            $groupName = 'Packages';
        }

        return [
            'group_name'   => $groupName,
            'article_name' => $articleName,
        ];
    }

    private function calculateBalance($operations): array
    {
        $weight   = 0;
        $price    = 0;
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

    /**
     * Calculate WAP for a tracking number.
     * Uses last 4 (or fewer) OUT operations. If 0 sells, returns null.
     */
    private function calculateWAP(WarehouseStockableTrackingNumber $tn): ?float
    {
        // If custom WAP is set, use it
        if ($tn->custom_wap !== null) {
            return (float) $tn->custom_wap;
        }

        $outOps = $tn->warehouse_stockable_tracking_number_operations
            ->where('type_operation', 'out')
            ->sortByDesc('date')
            ->take(4);

        if ($outOps->isEmpty()) {
            return null; // N/A
        }

        $totalWeight = $outOps->sum('total_weight');
        $totalPrice  = $outOps->sum('total_price');

        return $totalWeight > 0 ? ($totalPrice / $totalWeight) : null;
    }

    // ─────────────────────────────────────────
    //  Warehouse CRUD
    // ─────────────────────────────────────────

    /**
     * POST /api/warehouses
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|unique:warehouses,name',
            'description' => 'nullable|string|max:250',
            'warehouse_type' => 'nullable|in:FishType,GuanoType,PackageType',
            'warehouse_category_id' => 'nullable|exists:warehouse_categories,id',
            'has_sejour' => 'boolean',
            'operation_service_fee' => 'nullable|numeric|min:0',
        ]);

        $warehouse = Warehouse::create($data);

        return response()->json([
            'message' => 'Warehouse created successfully',
            'warehouse' => $warehouse->load('warehouse_category'),
        ], 201);
    }

    /**
     * PUT /api/warehouses/{warehouse}
     */
    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|unique:warehouses,name,' . $warehouse->id,
            'description' => 'nullable|string|max:250',
            'warehouse_type' => 'nullable|in:FishType,GuanoType,PackageType',
            'warehouse_category_id' => 'nullable|exists:warehouse_categories,id',
            'has_sejour' => 'boolean',
            'operation_service_fee' => 'nullable|numeric|min:0',
        ]);

        $warehouse->update($data);

        return response()->json([
            'message' => 'Warehouse updated successfully',
            'warehouse' => $warehouse->fresh()->load('warehouse_category'),
        ], 200);
    }

    /**
     * DELETE /api/warehouses/{warehouse}
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $warehouse->delete();
        return response()->json(['message' => 'Warehouse deleted successfully'], 200);
    }

    // ─────────────────────────────────────────
    //  Séjour Rate CRUD
    // ─────────────────────────────────────────

    /**
     * GET /api/warehouses/{warehouse}/sejour-rates
     */
    public function sejourRates(Warehouse $warehouse): JsonResponse
    {
        return response()->json([
            'rates' => $warehouse->sejour_rates,
        ], 200);
    }

    /**
     * POST /api/warehouses/{warehouse}/sejour-rates
     */
    public function storeSejourRate(Request $request, Warehouse $warehouse): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'daily_rate_per_kg' => 'required|numeric|min:0',
            'service_unit_price' => 'nullable|numeric|min:0',
            'weight_unit' => 'nullable|numeric|min:1',
        ]);

        // Check for overlapping date ranges on this warehouse
        $overlap = $this->checkDateOverlap($warehouse->id, $data['start_date'], $data['end_date'] ?? null);
        if ($overlap) {
            return response()->json([
                'message' => 'Date range overlaps with an existing rate period.',
                'error' => 'date_overlap',
            ], 422);
        }

        $rate = $warehouse->sejour_rates()->create($data);

        return response()->json([
            'message' => 'Séjour rate added',
            'rate' => $rate,
        ], 201);
    }

    /**
     * PUT /api/warehouses/sejour-rates/{id}
     */
    public function updateSejourRate(Request $request, $id): JsonResponse
    {
        $rate = \App\Models\WarehouseSejourRate::findOrFail($id);

        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'daily_rate_per_kg' => 'required|numeric|min:0',
            'service_unit_price' => 'nullable|numeric|min:0',
            'weight_unit' => 'nullable|numeric|min:1',
        ]);

        // Check overlap excluding the current rate being updated
        $overlap = $this->checkDateOverlap($rate->warehouse_id, $data['start_date'], $data['end_date'] ?? null, $rate->id);
        if ($overlap) {
            return response()->json([
                'message' => 'Date range overlaps with an existing rate period.',
                'error' => 'date_overlap',
            ], 422);
        }

        $rate->update($data);

        return response()->json([
            'message' => 'Séjour rate updated',
            'rate' => $rate->fresh(),
        ], 200);
    }

    /**
     * Check if a date range overlaps with existing rates for a warehouse.
     * If end_date is null, it means open-ended (ongoing), so it overlaps with anything after start_date.
     */
    private function checkDateOverlap(int $warehouseId, string $startDate, ?string $endDate, ?int $excludeId = null): bool
    {
        $query = \App\Models\WarehouseSejourRate::where('warehouse_id', $warehouseId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->where(function ($q) use ($startDate, $endDate) {
            if ($endDate) {
                // New range has both start and end
                $q->where(function ($sub) use ($startDate, $endDate) {
                    $sub->where(function ($s) use ($startDate, $endDate) {
                        // Existing range overlaps with new range
                        $s->where('start_date', '<=', $endDate)
                          ->where(function ($e) use ($startDate) {
                              $e->where('end_date', '>=', $startDate)
                                ->orWhereNull('end_date');
                          });
                    });
                });
            } else {
                // New range is open-ended (no end_date)
                $q->where(function ($sub) use ($startDate) {
                    $sub->where('end_date', '>=', $startDate)
                        ->orWhereNull('end_date');
                });
            }
        });

        return $query->exists();
    }

    /**
     * DELETE /api/warehouses/sejour-rates/{id}
     */
    public function deleteSejourRate($id): JsonResponse
    {
        $rate = \App\Models\WarehouseSejourRate::findOrFail($id);
        $rate->delete();

        return response()->json(['message' => 'Séjour rate deleted'], 200);
    }

    /**
     * GET /api/warehouses/{warehouse}/sejour?unit_kg=1000
     * Calculate séjour cost using the formula:
     *   séjour_cost   = days × daily_rate × T
     *   operation_cost = service_unit_price × 2 × T
     *   total_cost     = séjour_cost + operation_cost
     *
     * Where T = total_stock_weight / unit_kg.
     * Example: 30,000 KG in stock, unit = 1,000 KG → T = 30.
     */
    public function calculateSejour(Request $request, Warehouse $warehouse): JsonResponse
    {
        if (!$warehouse->has_sejour) {
            return response()->json(['message' => 'This warehouse does not have séjour enabled', 'items' => []], 200);
        }

        $unitKg = (float) $request->query('unit_kg', 1000);

        // ── Calculate total remaining weight in warehouse ──
        $warehouse->load([
            'warehouse_stockables.warehouse_stockable_tracking_numbers.warehouse_stockable_tracking_number_operations',
        ]);

        $totalStockWeight = 0;
        foreach ($warehouse->warehouse_stockables as $ws) {
            foreach ($ws->warehouse_stockable_tracking_numbers as $tn) {
                $balance = $this->calculateBalance($tn->warehouse_stockable_tracking_number_operations);
                $totalStockWeight += max($balance['weight'], 0);
            }
        }
        $totalStockWeight = round($totalStockWeight, 2);

        // T = total weight / unit
        $T = $unitKg > 0 ? ($totalStockWeight / $unitKg) : 0;

        $rates = $warehouse->sejour_rates()->orderBy('start_date')->get();

        // ── Build breakdown per rate period ──
        $items = [];
        $totalSejourCost = 0;
        $totalOperationCost = 0;
        $totalDays = 0;

        foreach ($rates as $rate) {
            $startDate = Carbon::parse($rate->start_date);
            $endDate = $rate->end_date ? Carbon::parse($rate->end_date) : Carbon::today();
            $daysInRange = max($startDate->diffInDays($endDate) + 1, 0);

            $dailyRate = $rate->daily_rate_per_kg ?? 0;
            $serviceRate = $rate->service_unit_price ?? 0;

            $sejourCost = round($daysInRange * $dailyRate * $T, 2);
            $operationCost = round($serviceRate * 2 * $T, 2);
            $totalCost = round($sejourCost + $operationCost, 2);

            $items[] = [
                'start_date' => $rate->start_date,
                'end_date' => $rate->end_date,
                'days_in_stock' => $daysInRange,
                'daily_rate' => $dailyRate,
                'service_rate' => $serviceRate,
                'sejour_cost' => $sejourCost,
                'operation_fee' => $operationCost,
                'total_cost' => $totalCost,
            ];

            $totalSejourCost += $sejourCost;
            $totalOperationCost += $operationCost;
            $totalDays += $daysInRange;
        }

        $grandTotal = round($totalSejourCost + $totalOperationCost, 2);

        return response()->json([
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
            ],
            'total_stock_weight' => $totalStockWeight,
            'unit_kg' => $unitKg,
            'T' => round($T, 4),
            'items' => $items,
            'summary' => [
                'total_sejour_cost' => round($totalSejourCost, 2),
                'total_operation_cost' => round($totalOperationCost, 2),
                'grand_total' => $grandTotal,
                'total_days' => $totalDays,
            ],
        ], 200);
    }

    /**
     * Find the applicable daily rate for a given date from the rates collection.
     */
    private function findRateForDate($rates, Carbon $date): array
    {
        foreach ($rates as $rate) {
            $start = Carbon::parse($rate->start_date);
            $end = $rate->end_date ? Carbon::parse($rate->end_date) : null;

            // If end_date is null, rate applies from start_date onwards indefinitely
            if ($end) {
                $matches = $date->between($start, $end);
            } else {
                $matches = $date->gte($start);
            }

            if ($matches) {
                return [
                    'daily_rate' => $rate->daily_rate_per_kg,
                    'service_rate' => $rate->service_unit_price ?? 0,
                ];
            }
        }
        return ['daily_rate' => 0, 'service_rate' => 0];
    }
}

