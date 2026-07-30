<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\DetailProduction;
use App\Models\Charge;
use App\Models\FishProcessType;
use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use App\Models\WarehouseBalanceOperation;
use App\Models\PackageSecondaryConfig;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    use BroadcastsModelChanges;
    /**
     * Production analytics: 4 stacked bar series + yield line series per fish_process_type.
     */
    public function analytics(Request $request)
    {
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');
        $mode = $request->input('mode', 'daily'); // daily | monthly | yearly

        // ── Serie 1: Fresh Reception (Kg) from charges with is_production=true ──
        $freshQuery = DB::table('detail_charges')
            ->join('charges', 'detail_charges.charge_id', '=', 'charges.id')
            ->join('charge_categories', 'charges.charge_category_id', '=', 'charge_categories.id')
            ->join('fish_types', function ($join) {
                $join->on('detail_charges.stockable_id', '=', 'fish_types.id')
                    ->where('detail_charges.stockable_type', '=', 'FishType');
            })
            ->whereNull('detail_charges.deleted_at')
            ->whereNull('charges.deleted_at')
            ->whereNull('charge_categories.deleted_at')
            ->where('charge_categories.is_production', true)
            ->where('fish_types.type', 'fresh');

        if ($dateStart && $dateEnd) {
            $freshQuery->whereBetween('charges.date', [$dateStart, $dateEnd]);
        }

        $freshDateExpr = $this->dateGroupExpr('charges.date', $mode);
        $freshData = $freshQuery->select(DB::raw("$freshDateExpr as date_key"), DB::raw('COALESCE(SUM(detail_charges.weight), 0) as total_weight'))
            ->groupBy('date_key')
            ->pluck('total_weight', 'date_key')
            ->toArray();

        // ── Serie 2: Frozen Production (Kg) from productions ──
        $frozenQuery = DB::table('detail_productions')
            ->join('productions', 'detail_productions.production_id', '=', 'productions.id')
            ->join('fish_types', function ($join) {
                $join->on('detail_productions.stockable_id', '=', 'fish_types.id')
                    ->where('detail_productions.stockable_type', '=', 'FishType');
            })
            ->whereNull('detail_productions.deleted_at')
            ->whereNull('productions.deleted_at')
            ->where('fish_types.type', 'frozen');

        if ($dateStart && $dateEnd) {
            $frozenQuery->whereBetween('productions.date', [$dateStart, $dateEnd]);
        }

        $frozenDateExpr = $this->dateGroupExpr('productions.date', $mode);
        $frozenData = $frozenQuery->select(DB::raw("$frozenDateExpr as date_key"), DB::raw('COALESCE(SUM(detail_productions.weight), 0) as total_weight'))
            ->groupBy('date_key')
            ->pluck('total_weight', 'date_key')
            ->toArray();

        // ── Serie 3: Guano Production (Kg) from productions ──
        $guanoQuery = DB::table('detail_productions')
            ->join('productions', 'detail_productions.production_id', '=', 'productions.id')
            ->whereNull('detail_productions.deleted_at')
            ->whereNull('productions.deleted_at')
            ->where('detail_productions.stockable_type', 'GuanoType');

        if ($dateStart && $dateEnd) {
            $guanoQuery->whereBetween('productions.date', [$dateStart, $dateEnd]);
        }

        $guanoDateExpr = $this->dateGroupExpr('productions.date', $mode);
        $guanoData = $guanoQuery->select(DB::raw("$guanoDateExpr as date_key"), DB::raw('COALESCE(SUM(detail_productions.weight), 0) as total_weight'))
            ->groupBy('date_key')
            ->pluck('total_weight', 'date_key')
            ->toArray();

        // ── Merge all date keys and compute Equilibrium ──
        $allKeys = collect(array_merge(
            array_keys($freshData),
            array_keys($frozenData),
            array_keys($guanoData)
        ))->unique()->sort()->values();

        $series = [
            'fresh_reception' => [],
            'frozen_production' => [],
            'guano_production' => [],
            'equilibrium' => [],
        ];

        foreach ($allKeys as $key) {
            $fresh = round((float) ($freshData[$key] ?? 0), 2);
            $frozen = round((float) ($frozenData[$key] ?? 0), 2);
            $guano = round((float) ($guanoData[$key] ?? 0), 2);
            $equilibrium = round(($frozen + $guano) - $fresh, 2);

            $series['fresh_reception'][] = ['x' => $key, 'y' => $fresh];
            $series['frozen_production'][] = ['x' => $key, 'y' => $frozen];
            $series['guano_production'][] = ['x' => $key, 'y' => $guano];
            $series['equilibrium'][] = ['x' => $key, 'y' => $equilibrium, 'label' => $equilibrium >= 0 ? 'surplus' : 'shortage'];
        }

        // ── Yield series per fish_process_type (line chart, %) ──
        $processTypes = FishProcessType::select('id', 'name', 'productivity_color')->get();
        $yieldSeries = [];

        foreach ($processTypes as $pt) {
            // Frozen production weight by date, filtered by fish_process_type
            $prodByPt = DB::table('detail_productions')
                ->join('productions', 'detail_productions.production_id', '=', 'productions.id')
                ->join('fish_types', function ($join) use ($pt) {
                    $join->on('detail_productions.stockable_id', '=', 'fish_types.id')
                        ->where('detail_productions.stockable_type', '=', 'FishType')
                        ->where('fish_types.fish_process_type_id', '=', $pt->id);
                })
                ->whereNull('detail_productions.deleted_at')
                ->whereNull('productions.deleted_at')
                ->where('fish_types.type', 'frozen');

            if ($dateStart && $dateEnd) {
                $prodByPt->whereBetween('productions.date', [$dateStart, $dateEnd]);
            }

            $prodPtData = $prodByPt->select(
                DB::raw("{$frozenDateExpr} as date_key"),
                DB::raw('COALESCE(SUM(detail_productions.weight), 0) as total_weight')
            )->groupBy('date_key')->pluck('total_weight', 'date_key')->toArray();

            // Fresh charge weight by date, filtered by fish_process_type
            $chargByPt = DB::table('detail_charges')
                ->join('charges', 'detail_charges.charge_id', '=', 'charges.id')
                ->join('charge_categories', 'charges.charge_category_id', '=', 'charge_categories.id')
                ->join('fish_types', function ($join) use ($pt) {
                    $join->on('detail_charges.stockable_id', '=', 'fish_types.id')
                        ->where('detail_charges.stockable_type', '=', 'FishType')
                        ->where('fish_types.fish_process_type_id', '=', $pt->id);
                })
                ->whereNull('detail_charges.deleted_at')
                ->whereNull('charges.deleted_at')
                ->whereNull('charge_categories.deleted_at')
                ->where('charge_categories.is_production', true)
                ->where('fish_types.type', 'fresh');

            if ($dateStart && $dateEnd) {
                $chargByPt->whereBetween('charges.date', [$dateStart, $dateEnd]);
            }

            $chargPtData = $chargByPt->select(
                DB::raw("{$freshDateExpr} as date_key"),
                DB::raw('COALESCE(SUM(detail_charges.weight), 0) as total_weight')
            )->groupBy('date_key')->pluck('total_weight', 'date_key')->toArray();

            // Calculate yield per date point: (production / charge) * 100
            $ptKeys = collect(array_merge(array_keys($prodPtData), array_keys($chargPtData)))->unique()->sort()->values();
            $data = [];
            $hasData = false;

            foreach ($allKeys as $key) {
                $chargeW = (float) ($chargPtData[$key] ?? 0);
                $prodW = (float) ($prodPtData[$key] ?? 0);
                $yield = $chargeW > 0 ? round(($prodW / $chargeW) * 100, 2) : 0;
                $data[] = ['x' => $key, 'y' => $yield];
                if ($prodW > 0 || $chargeW > 0)
                    $hasData = true;
            }

            // Only include process types that have data
            if ($hasData) {
                $yieldSeries[] = [
                    'name' => $pt->name,
                    'color' => $pt->productivity_color ?: '#FF9800',
                    'data' => $data,
                ];
            }
        }

        $series['yield_series'] = $yieldSeries;

        return response()->json($series);
    }

    /**
     * Helper: get the SQL expression to group dates by mode.
     */
    private function dateGroupExpr(string $column, string $mode): string
    {
        return match ($mode) {
            'monthly' => "DATE_FORMAT($column, '%Y-%m')",
            'yearly' => "YEAR($column)",
            default => "DATE($column)",
        };
    }

    public function index(Request $request)
    {
        $query = Production::with([
            'productionCategory',
            'supplier',
            'client',
            'charge',
            'supplierInvoice',
            'clientInvoice',
            'detailProductions.detailProductionCategory',
            'detailProductions.stockable',
            'detailProductions.warehouse',
            'detailProductions.packageType',
        ]);

        if ($request->has('date_start') && $request->has('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        }

        return response()->json(['productions' => $query->orderBy('date', 'desc')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'production_category_id' => 'required|exists:production_categories,id',
            'reception_tracking_number' => 'nullable|array',
            'tracking_onp_numbers' => 'nullable|array',
            'production_tracking_number' => 'nullable|array',
            'production_type' => 'nullable|string|in:intern,extern',
            'fishery_id' => 'nullable|exists:fisheries,id',
            'ship_id' => 'nullable|exists:ships,id',
            'origin_id' => 'nullable|exists:origins,id',
            'charge_id' => 'nullable|exists:charges,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'client_id' => 'nullable|exists:clients,id',
            'details' => 'required|array|min:1',
            'details.*.detail_production_category_id' => 'required|exists:detail_production_categories,id',
            'details.*.article_description' => 'nullable|string',
            'details.*.stockable_type' => 'nullable|string',
            'details.*.stockable_id' => 'nullable|integer',
            'details.*.weight' => 'nullable|numeric|min:0',
            'details.*.unit_price' => 'nullable|numeric|min:0',
            'details.*.total_price' => 'nullable|numeric|min:0',
            'details.*.package_type_id' => 'nullable|exists:package_types,id',
            'details.*.nbr_box' => 'nullable|numeric|min:0',
            'details.*.warehouse_id' => 'nullable|exists:warehouses,id',
            'details.*.warehouse_operation' => 'nullable|string|in:stock_in,stock_out',
            'details.*.truck_id' => 'nullable|exists:trucks,id',
        ]);

        return DB::transaction(function () use ($data) {
            $totalPrice = collect($data['details'])->sum('total_price') ?? 0;
            $totalWeight = collect($data['details'])->sum('weight') ?? 0;

            $production = Production::create([
                'date' => $data['date'],
                'total_price' => $totalPrice,
                'total_weight' => $totalWeight,
                'total_secondary_packaging_cost' => 0,
                'production_category_id' => $data['production_category_id'],
                'reception_tracking_number' => $data['reception_tracking_number'] ?? null,
                'tracking_onp_numbers' => $data['tracking_onp_numbers'] ?? null,
                'production_tracking_number' => $data['production_tracking_number'] ?? null,
                'production_type' => $data['production_type'] ?? null,
                'fishery_id' => $data['fishery_id'] ?? null,
                'ship_id' => $data['ship_id'] ?? null,
                'origin_id' => $data['origin_id'] ?? null,
                'charge_id' => $data['charge_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
            ]);

            $totalSecondaryPackagingCost = 0;

            foreach ($data['details'] as $detail) {
                $detail = $this->enrichDetailWithSecondaryPackaging($detail, $data['date']);
                $totalSecondaryPackagingCost += (float) ($detail['secondary_packaging_cost'] ?? 0);
                $dp = $production->detailProductions()->create($detail);
                $this->processWarehouseOperation($dp, $production);
            }

            $production->update(['total_secondary_packaging_cost' => round($totalSecondaryPackagingCost, 2)]);

            $production->load('productionCategory', 'supplier', 'client', 'charge.detailCharges.stockable', 'supplierInvoice', 'clientInvoice', 'detailProductions.detailProductionCategory', 'detailProductions.stockable', 'detailProductions.warehouse', 'detailProductions.packageType');
            $this->computeEquilibrium($production);
            $this->broadcastChange('Production', 'created', $production->id, $production->toArray());
            $this->broadcastChange('WarehouseBalance', 'updated', $production->id);
            return response()->json(['production' => $production], 201);
        });
    }

    public function show(Production $production)
    {
        $production->load(
            'detailProductions.detailProductionCategory',
            'detailProductions.packageType',
            'detailProductions.warehouse',
            'detailProductions.truck',
            'detailProductions.stockable',
            'supplier',
            'client',
            'productionCategory',
            'charge.detailCharges.stockable',
            'qualityControls.creator',
            'qualityControls.approver'
        );
        return response()->json(['production' => $production]);
    }

    public function update(Request $request, Production $production)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'production_category_id' => 'required|exists:production_categories,id',
            'reception_tracking_number' => 'nullable|array',
            'tracking_onp_numbers' => 'nullable|array',
            'production_tracking_number' => 'nullable|array',
            'production_type' => 'nullable|string|in:intern,extern',
            'fishery_id' => 'nullable|exists:fisheries,id',
            'ship_id' => 'nullable|exists:ships,id',
            'origin_id' => 'nullable|exists:origins,id',
            'charge_id' => 'nullable|exists:charges,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'client_id' => 'nullable|exists:clients,id',
            'details' => 'required|array|min:1',
            'details.*.detail_production_category_id' => 'required|exists:detail_production_categories,id',
            'details.*.article_description' => 'nullable|string',
            'details.*.stockable_type' => 'nullable|string',
            'details.*.stockable_id' => 'nullable|integer',
            'details.*.weight' => 'nullable|numeric|min:0',
            'details.*.unit_price' => 'nullable|numeric|min:0',
            'details.*.total_price' => 'nullable|numeric|min:0',
            'details.*.package_type_id' => 'nullable|exists:package_types,id',
            'details.*.nbr_box' => 'nullable|numeric|min:0',
            'details.*.warehouse_id' => 'nullable|exists:warehouses,id',
            'details.*.warehouse_operation' => 'nullable|string|in:stock_in,stock_out',
            'details.*.truck_id' => 'nullable|exists:trucks,id',
        ]);

        // Capture old state for audit trail
        $oldData = $production->toArray();

        return DB::transaction(function () use ($data, $production, $oldData) {
            $this->reverseWarehouseOperations($production);
            $production->detailProductions()->delete();

            $totalPrice = collect($data['details'])->sum('total_price') ?? 0;
            $totalWeight = collect($data['details'])->sum('weight') ?? 0;

            $production->update([
                'date' => $data['date'],
                'total_price' => $totalPrice,
                'total_weight' => $totalWeight,
                'total_secondary_packaging_cost' => 0,
                'production_category_id' => $data['production_category_id'],
                'reception_tracking_number' => $data['reception_tracking_number'] ?? null,
                'tracking_onp_numbers' => $data['tracking_onp_numbers'] ?? null,
                'production_tracking_number' => $data['production_tracking_number'] ?? null,
                'production_type' => $data['production_type'] ?? null,
                'fishery_id' => $data['fishery_id'] ?? null,
                'ship_id' => $data['ship_id'] ?? null,
                'origin_id' => $data['origin_id'] ?? null,
                'charge_id' => $data['charge_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
            ]);

            $totalSecondaryPackagingCost = 0;

            foreach ($data['details'] as $detail) {
                $detail = $this->enrichDetailWithSecondaryPackaging($detail, $data['date']);
                $totalSecondaryPackagingCost += (float) ($detail['secondary_packaging_cost'] ?? 0);
                $dp = $production->detailProductions()->create($detail);
                $this->processWarehouseOperation($dp, $production);
            }

            $production->update(['total_secondary_packaging_cost' => round($totalSecondaryPackagingCost, 2)]);

            $production->load('productionCategory', 'supplier', 'client', 'charge.detailCharges.stockable', 'supplierInvoice', 'clientInvoice', 'detailProductions.detailProductionCategory', 'detailProductions.stockable', 'detailProductions.warehouse', 'detailProductions.packageType');
            $this->computeEquilibrium($production);
            $this->broadcastChange('Production', 'updated', $production->id, $production->toArray(), $oldData);
            $this->broadcastChange('WarehouseBalance', 'updated', $production->id);
            return response()->json(['production' => $production]);
        });
    }

    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids))
            return response()->json(['message' => 'No IDs provided'], 400);

        DB::transaction(function () use ($ids) {
            $productions = Production::whereIn('id', $ids)->get();
            foreach ($productions as $production) {
                $this->reverseWarehouseOperations($production);
                $production->delete();
            }
        });

        foreach ($ids as $id) {
            $this->broadcastChange('Production', 'deleted', (int) $id);
        }
        $this->broadcastChange('WarehouseBalance', 'updated', 0);

        return response()->json(['message' => 'Deleted', 'count' => count($ids)]);
    }

    public function checkProductionTrackingNumberUniqueness(Request $request)
    {
        $data = $request->validate([
            'tracking_number' => 'required|string',
            'production_category_id' => 'required|exists:production_categories,id',
        ]);

        $exists = Production::where('production_category_id', $data['production_category_id'])
            ->whereJsonContains('production_tracking_number', $data['tracking_number'])
            ->first();

        if ($exists) {
            $supplierName = $exists->supplier?->name ?? '—';
            return response()->json([
                'exists' => true,
                'production' => [
                    'date' => $exists->date->format('Y-m-d'),
                    'suppliers' => $supplierName,
                    'weight' => $exists->total_weight,
                    'price' => $exists->total_price,
                ]
            ]);
        }

        return response()->json(['exists' => false]);
    }

    public function checkReceptionTrackingNumberUniqueness(Request $request)
    {
        $data = $request->validate([
            'tracking_number' => 'required|string',
            'production_category_id' => 'required|exists:production_categories,id',
        ]);

        $exists = Production::where('production_category_id', $data['production_category_id'])
            ->whereJsonContains('reception_tracking_number', $data['tracking_number'])
            ->first();

        if ($exists) {
            $supplierName = $exists->supplier?->name ?? '—';
            return response()->json([
                'exists' => true,
                'production' => [
                    'date' => $exists->date->format('Y-m-d'),
                    'suppliers' => $supplierName,
                    'weight' => $exists->total_weight,
                    'price' => $exists->total_price,
                ]
            ]);
        }

        return response()->json(['exists' => false]);
    }

    public function checkOnpTrackingNumberUniqueness(Request $request)
    {
        $data = $request->validate([
            'tracking_number' => 'required|string',
            'production_category_id' => 'required|exists:production_categories,id',
        ]);

        $exists = Production::where('production_category_id', $data['production_category_id'])
            ->whereJsonContains('tracking_onp_numbers', $data['tracking_number'])
            ->first();

        if ($exists) {
            $supplierName = $exists->supplier?->name ?? '—';
            return response()->json([
                'exists' => true,
                'production' => [
                    'date' => $exists->date->format('Y-m-d'),
                    'suppliers' => $supplierName,
                    'weight' => $exists->total_weight,
                    'price' => $exists->total_price,
                ]
            ]);
        }

        return response()->json(['exists' => false]);
    }

    private function processWarehouseOperation(DetailProduction $dp, Production $production): void
    {
        if (!$dp->warehouse_id)
            return;

        $weight = $dp->weight ?? 0;
        $totalPrice = $dp->total_price ?? 0;
        $packages = $dp->nbr_box ?? 0;
        $supplierName = $production->supplier?->name ?? '';

        $trackingNumber = $production->reception_tracking_number[0] ?? null;
        if (!$trackingNumber && $production->charge) {
            $trackingNumber = $production->charge->reception_tracking_number[0] ?? null;
        }

        $balanceDate = $production->date;
        if ($production->charge) {
            $balanceDate = $production->charge->date;
        }

        $originName = $production->origin?->name;
        $fisheryName = $production->fishery?->name;
        if ($production->charge) {
            if (!$originName)
                $originName = $production->charge->origin?->name;
            if (!$fisheryName)
                $fisheryName = $production->charge->fishery?->name;
            if (!$supplierName && $production->charge && $production->charge->supplier) {
                $supplierName = $production->charge->supplier->name;
            }
        }

        // 1. Primary Stock Operation (for stockable items like FishType, GuanoType, PackageType)
        if ($dp->warehouse_operation && $dp->stockable_type && $dp->stockable_id) {
            if ($dp->warehouse_operation === 'stock_in') {
                WarehouseBalance::create([
                    'warehouse_id' => $dp->warehouse_id,
                    'stockable_type' => $dp->stockable_type,
                    'stockable_id' => $dp->stockable_id,
                    'reception_tracking_number' => $trackingNumber,
                    'warehouse_operation' => 'stock_in',
                    'date' => $balanceDate,
                    'total_weight' => $weight,
                    'remaining_weight' => $weight,
                    'total_price' => $totalPrice,
                    'unit_price' => $dp->unit_price ?? 0,
                    'package_count' => $packages,
                    'package_type_id' => $dp->package_type_id,
                    'supplier_name' => $supplierName ?: null,
                    'origin_name' => $originName,
                    'fishery_name' => $fisheryName,
                    'source_type' => 'DetailProduction',
                    'source_id' => $dp->id,
                    'is_finished' => false,
                ]);
            } else {
                $balance = WarehouseBalance::where('warehouse_id', $dp->warehouse_id)
                    ->where('stockable_type', $dp->stockable_type)
                    ->where('stockable_id', $dp->stockable_id)
                    ->where('warehouse_operation', 'stock_in')
                    ->where('is_finished', false)
                    ->when($trackingNumber, fn($q) => $q->where('reception_tracking_number', $trackingNumber))
                    ->orderBy('date', 'asc')
                    ->first();

                if ($balance) {
                    WarehouseBalanceOperation::create([
                        'warehouse_balance_id' => $balance->id,
                        'date' => $production->date,
                        'weight' => $weight,
                        'unit_price' => $dp->unit_price ?? 0,
                        'total_price' => $totalPrice,
                        'package_count' => $packages,
                        'source_type' => 'DetailProduction',
                        'source_id' => $dp->id,
                        'client_name' => null,
                    ]);
                    $balance->recalculateRemaining();
                }
            }
        }

        // 2. Secondary Packaging Stock-out from Warehouse when category & warehouse toggles are enabled
        $category = $dp->detailProductionCategory;
        if ($category && $category->allow_secondary_packaging) {
            $targetWarehouseId = null;
            if ($dp->warehouse && $dp->warehouse->allow_production_effect) {
                $targetWarehouseId = $dp->warehouse_id;
            } else {
                $effWarehouse = Warehouse::where('allow_production_effect', true)->first();
                if ($effWarehouse) {
                    $targetWarehouseId = $effWarehouse->id;
                }
            }

            if ($targetWarehouseId) {
                $packageTypeId = $dp->package_type_id;
                $nbrBox = (float) ($dp->nbr_box ?? 0);
                if ($packageTypeId && $nbrBox > 0) {
                    $remainingToDeduct = $nbrBox;
                    $packageBalances = WarehouseBalance::where('warehouse_id', $targetWarehouseId)
                        ->where(function ($q) use ($packageTypeId) {
                            $q->where(function ($q2) use ($packageTypeId) {
                                $q2->where('stockable_type', 'PackageType')
                                   ->where('stockable_id', $packageTypeId);
                            })->orWhere('package_type_id', $packageTypeId);
                        })
                        ->where('warehouse_operation', 'stock_in')
                        ->where('is_finished', false)
                        ->orderBy('date', 'asc')
                        ->get();

                    foreach ($packageBalances as $packageBalance) {
                        if ($remainingToDeduct <= 0) break;
                        $alreadyOut = (float) $packageBalance->operations()->sum('package_count');
                        $avail = max(0, (float)($packageBalance->package_count ?? 0) - $alreadyOut);
                        if ($avail <= 0) continue;

                        $deductCount = min($remainingToDeduct, $avail);
                        WarehouseBalanceOperation::create([
                            'warehouse_balance_id' => $packageBalance->id,
                            'date' => $production->date,
                            'weight' => 0,
                            'unit_price' => $packageBalance->unit_price ?? 0,
                            'total_price' => round(($packageBalance->unit_price ?? 0) * $deductCount, 2),
                            'package_count' => $deductCount,
                            'source_type' => 'DetailProduction',
                            'source_id' => $dp->id,
                            'client_name' => null,
                        ]);
                        $packageBalance->recalculateRemaining();
                        $remainingToDeduct -= $deductCount;
                    }
                }
            }
        }
    }

    private function reverseWarehouseOperations(Production $production): void
    {
        foreach ($production->detailProductions as $dp) {
            if (!$dp->warehouse_id)
                continue;

            WarehouseBalance::where('source_type', 'DetailProduction')
                ->where('source_id', $dp->id)
                ->delete();

            $ops = WarehouseBalanceOperation::where('source_type', 'DetailProduction')
                ->where('source_id', $dp->id)
                ->get();
            foreach ($ops as $op) {
                $balance = $op->warehouseBalance;
                $op->delete();
                if ($balance)
                    $balance->recalculateRemaining();
            }
        }
    }

    /**
     * Compute and persist equilibrium fields on a production.
     * fresh_reception = SUM(weight) of charge detail_charges where stockable is FishType with type=fresh
     * frozen_production = SUM(weight) of detail_productions where stockable is FishType with type=frozen
     * guano_production = SUM(weight) of detail_productions where stockable_type=GuanoType
     * equilibrium = (frozen + guano) - fresh
     */
    private function computeEquilibrium(Production $production): void
    {
        // Fresh reception from linked charge
        $freshWeight = 0;
        if ($production->charge && $production->charge->detailCharges) {
            foreach ($production->charge->detailCharges as $dc) {
                if ($dc->stockable_type === 'FishType' && $dc->stockable && $dc->stockable->type === 'fresh') {
                    $freshWeight += (float) $dc->weight;
                }
            }
        }

        // Frozen production from detail_productions
        $frozenWeight = 0;
        foreach ($production->detailProductions as $dp) {
            if ($dp->stockable_type === 'FishType' && $dp->stockable && $dp->stockable->type === 'frozen') {
                $frozenWeight += (float) $dp->weight;
            }
        }

        // Guano production from detail_productions
        $guanoWeight = 0;
        foreach ($production->detailProductions as $dp) {
            if ($dp->stockable_type === 'GuanoType') {
                $guanoWeight += (float) $dp->weight;
            }
        }

        $equilibrium = round(($frozenWeight + $guanoWeight) - $freshWeight, 2);
        $status = $equilibrium > 0 ? 'surplus' : ($equilibrium < 0 ? 'shortage' : 'balanced');

        $production->update([
            'fresh_reception_weight' => round($freshWeight, 2),
            'frozen_production_weight' => round($frozenWeight, 2),
            'guano_production_weight' => round($guanoWeight, 2),
            'equilibrium' => $equilibrium,
            'equilibrium_status' => $status,
        ]);
    }

    /**
     * Enrich detail production input with secondary packaging cost calculation if enabled for category.
     */
    private function enrichDetailWithSecondaryPackaging(array $detail, string $date): array
    {
        $detailCategoryId = $detail['detail_production_category_id'] ?? null;
        $packageTypeId = $detail['package_type_id'] ?? null;
        $nbrBox = (float) ($detail['nbr_box'] ?? 0);

        if (!$detailCategoryId || !$packageTypeId || $nbrBox <= 0) {
            $detail['secondary_packaging_cost'] = 0;
            $detail['secondary_packaging_details'] = null;
            return $detail;
        }

        $category = \App\Models\DetailProductionCategory::find($detailCategoryId);
        if (!$category || !$category->allow_secondary_packaging) {
            $detail['secondary_packaging_cost'] = 0;
            $detail['secondary_packaging_details'] = null;
            return $detail;
        }

        $config = \App\Models\PackageSecondaryConfig::getConfigForPackageAndDate($packageTypeId, $date);
        if (!$config) {
            $detail['secondary_packaging_cost'] = 0;
            $detail['secondary_packaging_details'] = null;
            return $detail;
        }

        $unitCost = $config->computeUnitCost();
        $totalSecCost = round($nbrBox * $unitCost, 2);

        $detail['secondary_packaging_cost'] = $totalSecCost;
        $detail['secondary_packaging_details'] = [
            'config_id' => $config->id,
            'unit_cost' => $unitCost,
            'nbr_box' => $nbrBox,
            'caisses_qte' => (float) $config->caisses_qte,
            'caisses_price' => (float) $config->caisses_price,
            'cellophane_qte' => (float) $config->cellophane_qte,
            'cellophane_price' => (float) $config->cellophane_price,
            'plastique_qte' => (float) $config->plastique_qte,
            'plastique_price' => (float) $config->plastique_price,
            'strapex_qte' => (float) $config->strapex_qte,
            'strapex_price' => (float) $config->strapex_price,
            'palletes_qte' => (float) $config->palletes_qte,
            'palletes_price' => (float) $config->palletes_price,
            'scotch_qte' => (float) $config->scotch_qte,
            'scotch_price' => (float) $config->scotch_price,
        ];

        return $detail;
    }
}
