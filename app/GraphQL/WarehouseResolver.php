<?php

namespace App\GraphQL;

use App\Http\Controllers\WarehouseController;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use Illuminate\Http\Request;

class WarehouseResolver
{
    public static function getRootValue(): array
    {
        return [
            // Queries
            'warehouses' => fn($root, $args, $ctx) => self::resolveWarehouses($root, $args, $ctx),
            'warehouse' => fn($root, $args, $ctx) => self::resolveWarehouse($root, $args, $ctx),
            'warehouseCategories' => fn($root, $args, $ctx) => self::resolveWarehouseCategories($root, $args, $ctx),
            'chambersOverview' => fn($root, $args, $ctx) => self::resolveChambersOverview($root, $args, $ctx),
            'speciesOverview' => fn($root, $args, $ctx) => self::resolveSpeciesOverview($root, $args, $ctx),
            'stockParLot' => fn($root, $args, $ctx) => self::resolveStockParLot($root, $args, $ctx),
            'stockMovementsJournal' => fn($root, $args, $ctx) => self::resolveStockMovementsJournal($root, $args, $ctx),
            'warehouseAnalytics' => fn($root, $args, $ctx) => self::resolveWarehouseAnalytics($root, $args, $ctx),
            'traceabilityVouchers' => fn($root, $args, $ctx) => self::resolveTraceabilityVouchers($root, $args, $ctx),
            'traceabilityVoucherArticles' => fn($root, $args, $ctx) => self::resolveTraceabilityVoucherArticles($root, $args, $ctx),
            'traceabilityPedigreeTimeline' => fn($root, $args, $ctx) => self::resolveTraceabilityPedigreeTimeline($root, $args, $ctx),
            'lotTraceability' => fn($root, $args, $ctx) => self::resolveLotTraceability($root, $args, $ctx),

            // Mutations
            'createWarehouse' => fn($root, $args, $ctx) => self::resolveCreateWarehouse($root, $args, $ctx),
            'updateWarehouse' => fn($root, $args, $ctx) => self::resolveUpdateWarehouse($root, $args, $ctx),
            'deleteWarehouse' => fn($root, $args, $ctx) => self::resolveDeleteWarehouse($root, $args, $ctx),
        ];
    }

    public static function resolveWarehouses($root, array $args, $context)
    {
        return Warehouse::with('category')->orderBy('name', 'asc')->get()->map(function ($w) {
            return [
                'id' => $w->id,
                'name' => $w->name,
                'capacity' => (float) $w->capacity,
                'warehouse_category_id' => $w->warehouse_category_id,
                'category' => $w->category ? [
                    'id' => $w->category->id,
                    'name' => $w->category->name,
                    'description' => $w->category->description,
                ] : null,
                'created_at' => (string) $w->created_at,
                'updated_at' => (string) $w->updated_at,
            ];
        })->all();
    }

    public static function resolveWarehouse($root, array $args, $context)
    {
        $w = Warehouse::with('category')->find($args['id'] ?? null);
        if (!$w) return null;
        return [
            'id' => $w->id,
            'name' => $w->name,
            'capacity' => (float) $w->capacity,
            'warehouse_category_id' => $w->warehouse_category_id,
            'category' => $w->category ? [
                'id' => $w->category->id,
                'name' => $w->category->name,
                'description' => $w->category->description,
            ] : null,
            'created_at' => (string) $w->created_at,
            'updated_at' => (string) $w->updated_at,
        ];
    }

    public static function resolveWarehouseCategories($root, array $args, $context)
    {
        return WarehouseCategory::all()->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'description' => $cat->description,
            ];
        })->all();
    }

    public static function resolveChambersOverview($root, array $args, $context)
    {
        $controller = app(WarehouseController::class);
        $resp = $controller->chambersOverview(new Request($args));
        $data = $resp->getData(true);
        $chambers = $data['chambers'] ?? [];
        foreach ($chambers as &$ch) {
            $ch['lots_count'] = $ch['active_lots'] ?? 0;
            $ch['total_value'] = $ch['valuation'] ?? 0;
        }
        return $chambers;
    }

    public static function resolveSpeciesOverview($root, array $args, $context)
    {
        $controller = app(WarehouseController::class);
        $resp = $controller->speciesOverview(new Request($args));
        $data = $resp->getData(true);
        $species = $data['species'] ?? [];
        foreach ($species as &$sp) {
            $sp['species_name'] = $sp['article_name'] ?? '';
            $sp['total_value'] = $sp['total_valuation'] ?? 0;
        }
        return $species;
    }

    public static function resolveStockParLot($root, array $args, $context)
    {
        $controller = app(WarehouseController::class);
        $resp = $controller->stockParLot(new Request($args));
        $data = $resp->getData(true);

        return [
            'lots' => $data['lots'] ?? [],
            'summary' => $data['summary'] ?? null,
            'pagination' => $data['pagination'] ?? null,
            'balances' => [],
            'total_weight' => (float) ($data['summary']['total_remaining_weight'] ?? 0),
            'total_boxes' => (float) ($data['summary']['total_initial_boxes'] ?? 0),
            'total_value' => (float) ($data['summary']['total_valuation'] ?? 0),
            'total_count' => (int) ($data['pagination']['total'] ?? 0),
        ];
    }

    public static function resolveStockMovementsJournal($root, array $args, $context)
    {
        $controller = app(WarehouseController::class);
        $resp = $controller->stockMovementsJournal(new Request($args));
        $data = $resp->getData(true);

        return [
            'totals' => $data['totals'] ?? [
                'total_count' => 0,
                'total_in_weight' => 0,
                'total_out_weight' => 0,
                'net_weight' => 0,
            ],
            'movements' => $data['movements'] ?? [],
        ];
    }

    public static function resolveWarehouseAnalytics($root, array $args, $context)
    {
        $controller = app(WarehouseController::class);
        $resp = $controller->analytics(new Request($args));
        $data = $resp->getData(true);

        // Normalize aging buckets for easy GraphQL querying
        if (isset($data['aging']['buckets'])) {
            $buckets = $data['aging']['buckets'];
            foreach ($buckets as $bKey => &$bucket) {
                $bucket['lots_count'] = $bucket['lots'] ?? 0;
            }
            unset($bucket);

            $data['aging']['buckets'] = $buckets;
            $data['aging']['fresh'] = $buckets['fresh'] ?? null;
            $data['aging']['optimal'] = $buckets['optimal'] ?? null;
            $data['aging']['warning'] = $buckets['warning'] ?? null;
            $data['aging']['alert'] = $buckets['alert'] ?? null;
            $data['aging']['critical'] = $buckets['alert'] ?? null;
        }

        return $data;
    }

    public static function resolveTraceabilityVouchers($root, array $args, $context)
    {
        $controller = app(WarehouseController::class);
        $resp = $controller->searchTraceabilityVouchers(new Request($args));
        $data = $resp->getData(true);
        return $data['vouchers'] ?? [];
    }

    public static function resolveTraceabilityVoucherArticles($root, array $args, $context)
    {
        $controller = app(WarehouseController::class);
        $resp = $controller->traceabilityVoucherArticles(new Request($args));
        $data = $resp->getData(true);
        return $data['articles'] ?? [];
    }

    public static function resolveTraceabilityPedigreeTimeline($root, array $args, $context)
    {
        $controller = app(WarehouseController::class);
        $resp = $controller->traceabilityPedigreeTimeline(new Request($args));
        return $resp->getData(true);
    }

    public static function resolveLotTraceability($root, array $args, $context)
    {
        $controller = app(WarehouseController::class);
        $lotNum = $args['lot_number'] ?? '';
        $resp = $controller->lotTraceability(new Request(), $lotNum);
        return $resp->getData(true);
    }

    // Mutations
    public static function resolveCreateWarehouse($root, array $args, $context)
    {
        $w = Warehouse::create([
            'name' => $args['name'],
            'capacity' => $args['capacity'] ?? null,
            'warehouse_category_id' => $args['warehouse_category_id'] ?? null,
        ]);
        return self::resolveWarehouse($root, ['id' => $w->id], $context);
    }

    public static function resolveUpdateWarehouse($root, array $args, $context)
    {
        $w = Warehouse::findOrFail($args['id']);
        if (isset($args['name'])) $w->name = $args['name'];
        if (isset($args['capacity'])) $w->capacity = $args['capacity'];
        if (isset($args['warehouse_category_id'])) $w->warehouse_category_id = $args['warehouse_category_id'];
        $w->save();
        return self::resolveWarehouse($root, ['id' => $w->id], $context);
    }

    public static function resolveDeleteWarehouse($root, array $args, $context)
    {
        $w = Warehouse::findOrFail($args['id']);
        return (bool) $w->delete();
    }
}
