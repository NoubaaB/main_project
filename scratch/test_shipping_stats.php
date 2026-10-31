<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Shipping;
use App\Models\ShippingCategory;
use Carbon\Carbon;

$start = Carbon::parse('2026-01-01')->startOfDay();
$end = Carbon::parse('2026-12-31')->endOfDay();

$shippingsBaseQuery = Shipping::whereBetween('date', [$start, $end]);
$shipSummary = (clone $shippingsBaseQuery)
    ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight),0) as tw, COALESCE(SUM(total_price),0) as tp')
    ->first();

$grandShippingsWeight = max(0.001, (float)($shipSummary->tw ?? 0));
$grandShippingsPrice  = max(0.001, (float)($shipSummary->tp ?? 0));

$shippingCategories = ShippingCategory::all();
$stats = [];

foreach ($shippingCategories as $sc) {
    $catQuery = (clone $shippingsBaseQuery)->where('shipping_category_id', $sc->id);
    $catCount = (clone $catQuery)->count();
    $catTotalW = (float) (clone $catQuery)->sum('total_weight');
    $catTotalP = (float) (clone $catQuery)->sum('total_price');

    $hasWeight = $catTotalW > 0;
    $isExport = (bool) ($sc->enable_ship_id || str_contains(strtolower($sc->name), 'export'));

    $stats[] = [
        'id'                 => $sc->id,
        'name'               => $sc->name,
        'name_fr'            => $sc->name_fr ?: $sc->name,
        'name_en'            => $sc->name_en ?: $sc->name,
        'name_es'            => $sc->name_es ?: $sc->name,
        'description'        => $sc->description,
        'is_export'          => $isExport,
        'count'              => $catCount,
        'total_weight'       => round($catTotalW, 2),
        'total_price'        => round($catTotalP, 2),
        'has_weight'         => $hasWeight,
        'avg_price_per_kg'   => $hasWeight ? round($catTotalP / $catTotalW, 2) : null,
        'avg_price'          => $catCount > 0 ? round($catTotalP / $catCount, 2) : 0,
        'avg_weight'         => ($catCount > 0 && $hasWeight) ? round($catTotalW / $catCount, 2) : 0,
        'price_percentage'   => round(($catTotalP / $grandShippingsPrice) * 100, 2),
        'weight_percentage'  => $hasWeight ? round(($catTotalW / $grandShippingsWeight) * 100, 2) : 0,
    ];
}

$uncatShipCount = (clone $shippingsBaseQuery)->whereNull('shipping_category_id')->count();
if ($uncatShipCount > 0) {
    $uncatW = (float) (clone $shippingsBaseQuery)->whereNull('shipping_category_id')->sum('total_weight');
    $uncatP = (float) (clone $shippingsBaseQuery)->whereNull('shipping_category_id')->sum('total_price');
    $hasWeight = $uncatW > 0;
    $stats[] = [
        'id'                 => 0,
        'name'               => 'Uncategorized',
        'name_fr'            => 'Non catégorisé',
        'name_en'            => 'Uncategorized',
        'name_es'            => 'Sin categoría',
        'description'        => null,
        'is_export'          => false,
        'count'              => $uncatShipCount,
        'total_weight'       => round($uncatW, 2),
        'total_price'        => round($uncatP, 2),
        'has_weight'         => $hasWeight,
        'avg_price_per_kg'   => $hasWeight ? round($uncatP / $uncatW, 2) : null,
        'avg_price'          => round($uncatP / $uncatShipCount, 2),
        'avg_weight'         => $hasWeight ? round($uncatW / $uncatShipCount, 2) : 0,
        'price_percentage'   => round(($uncatP / $grandShippingsPrice) * 100, 2),
        'weight_percentage'  => $hasWeight ? round(($uncatW / $grandShippingsWeight) * 100, 2) : 0,
    ];
}

usort($stats, fn($a, $b) => $b['total_price'] <=> $a['total_price']);

echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

