<?php

namespace App\Services;

use App\Models\DetailProductionCategory;
use App\Models\PackageSecondaryConfig;
use App\Models\Production;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SecondaryPackagingService
{
    use BroadcastsModelChanges;

    /**
     * Cache helper to locate the matching secondary configuration in memory.
     */
    public function findConfigInMemory(int $packageTypeId, string $date, Collection $configs): ?PackageSecondaryConfig
    {
        $dateStr = substr($date, 0, 10);
        return $configs->first(function ($c) use ($packageTypeId, $dateStr) {
            if ($c->package_type_id != $packageTypeId) {
                return false;
            }
            $ds = is_string($c->date_start) ? substr($c->date_start, 0, 10) : $c->date_start->format('Y-m-d');
            if ($ds > $dateStr) {
                return false;
            }
            if ($c->date_end) {
                $de = is_string($c->date_end) ? substr($c->date_end, 0, 10) : $c->date_end->format('Y-m-d');
                if ($de < $dateStr) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Enrich detail production data array with secondary packaging cost & breakdown.
     */
    public function enrichDetail(array $detail, string $date, ?Collection $categories = null, ?Collection $configs = null): array
    {
        $detailCategoryId = $detail['detail_production_category_id'] ?? null;
        $packageTypeId = $detail['package_type_id'] ?? null;
        $nbrBox = (float) ($detail['nbr_box'] ?? 0);

        if (!$detailCategoryId || !$packageTypeId || $nbrBox <= 0) {
            $detail['secondary_packaging_cost'] = 0;
            $detail['secondary_packaging_details'] = null;
            return $detail;
        }

        $category = $categories ? $categories->get($detailCategoryId) : DetailProductionCategory::find($detailCategoryId);
        if (!$category || !$category->allow_secondary_packaging) {
            $detail['secondary_packaging_cost'] = 0;
            $detail['secondary_packaging_details'] = null;
            return $detail;
        }

        if ($configs) {
            $config = $this->findConfigInMemory((int) $packageTypeId, $date, $configs);
        } else {
            $config = PackageSecondaryConfig::getConfigForPackageAndDate($packageTypeId, $date);
        }

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
            'components' => $config->resolved_components,
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

    /**
     * Recalculate secondary packaging for a single production.
     */
    public function recalculateProduction(Production $production, ?Collection $categories = null, ?Collection $configs = null): float
    {
        if (!$categories) {
            $categories = DetailProductionCategory::all()->keyBy('id');
        }
        if (!$configs) {
            $configs = PackageSecondaryConfig::orderBy('date_start', 'desc')->orderBy('id', 'desc')->get();
        }

        $prodDate = is_string($production->date) ? substr($production->date, 0, 10) : $production->date->format('Y-m-d');
        $totalSecondaryCost = 0.0;

        foreach ($production->detailProductions as $dp) {
            $cat = $categories->get($dp->detail_production_category_id);
            if (!$cat || !$cat->allow_secondary_packaging || !$dp->package_type_id || (float) $dp->nbr_box <= 0) {
                if ($dp->secondary_packaging_cost != 0 || $dp->secondary_packaging_details !== null) {
                    $dp->update([
                        'secondary_packaging_cost' => 0,
                        'secondary_packaging_details' => null,
                    ]);
                }
                continue;
            }

            $config = $this->findConfigInMemory((int) $dp->package_type_id, $prodDate, $configs);
            if (!$config) {
                if ($dp->secondary_packaging_cost != 0 || $dp->secondary_packaging_details !== null) {
                    $dp->update([
                        'secondary_packaging_cost' => 0,
                        'secondary_packaging_details' => null,
                    ]);
                }
                continue;
            }

            $unitCost = $config->computeUnitCost();
            $secCost = round((float) $dp->nbr_box * $unitCost, 2);
            $secDetails = [
                'config_id' => $config->id,
                'unit_cost' => $unitCost,
                'nbr_box' => (float) $dp->nbr_box,
                'components' => $config->resolved_components,
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

            $dp->update([
                'secondary_packaging_cost' => $secCost,
                'secondary_packaging_details' => $secDetails,
            ]);

            $totalSecondaryCost += $secCost;
        }

        $totalSecondaryCost = round($totalSecondaryCost, 2);
        if ($production->total_secondary_packaging_cost != $totalSecondaryCost) {
            $production->update(['total_secondary_packaging_cost' => $totalSecondaryCost]);
        }

        return $totalSecondaryCost;
    }

    /**
     * Recalculate productions affected by a package type secondary configuration change.
     */
    public function recalculateForPackageType(int $packageTypeId, ?string $dateStart = null, ?string $dateEnd = null): array
    {
        $categories = DetailProductionCategory::all()->keyBy('id');
        $configs = PackageSecondaryConfig::orderBy('date_start', 'desc')->orderBy('id', 'desc')->get();

        $query = Production::with('detailProductions')
            ->whereHas('detailProductions', function ($q) use ($packageTypeId) {
                $q->where('package_type_id', $packageTypeId);
            });

        if ($dateStart) {
            $query->where('date', '>=', substr($dateStart, 0, 10));
        }
        if ($dateEnd) {
            $query->where('date', '<=', substr($dateEnd, 0, 10));
        }

        $productions = $query->get();
        $count = 0;
        $totalPackagingCost = 0.0;

        DB::transaction(function () use ($productions, $categories, $configs, &$count, &$totalPackagingCost) {
            foreach ($productions as $production) {
                $cost = $this->recalculateProduction($production, $categories, $configs);
                $totalPackagingCost += $cost;
                $count++;
            }
        });

        // Broadcast refresh so all clients update in real time
        $this->broadcastChange('Production', 'refresh', 0, []);

        return [
            'productions_count' => $count,
            'total_secondary_packaging_cost' => round($totalPackagingCost, 2),
        ];
    }

    /**
     * Recalculate secondary packaging for a given date range or specific productions.
     */
    public function recalculateForDateRange(?string $dateStart = null, ?string $dateEnd = null, ?int $packageTypeId = null, ?array $productionIds = null): array
    {
        $categories = DetailProductionCategory::all()->keyBy('id');
        $configs = PackageSecondaryConfig::orderBy('date_start', 'desc')->orderBy('id', 'desc')->get();

        $query = Production::with('detailProductions');

        if (!empty($productionIds)) {
            $query->whereIn('id', $productionIds);
        } else {
            if ($dateStart) {
                $query->where('date', '>=', substr($dateStart, 0, 10));
            }
            if ($dateEnd) {
                $query->where('date', '<=', substr($dateEnd, 0, 10));
            }
            if ($packageTypeId) {
                $query->whereHas('detailProductions', function ($q) use ($packageTypeId) {
                    $q->where('package_type_id', $packageTypeId);
                });
            } else {
                $query->whereHas('detailProductions', function ($q) {
                    $q->whereNotNull('package_type_id');
                });
            }
        }

        $productions = $query->get();
        $count = 0;
        $totalPackagingCost = 0.0;

        DB::transaction(function () use ($productions, $categories, $configs, &$count, &$totalPackagingCost) {
            foreach ($productions as $production) {
                $cost = $this->recalculateProduction($production, $categories, $configs);
                $totalPackagingCost += $cost;
                $count++;
            }
        });

        // Broadcast refresh
        $this->broadcastChange('Production', 'refresh', 0, []);

        return [
            'productions_count' => $count,
            'total_secondary_packaging_cost' => round($totalPackagingCost, 2),
        ];
    }
}
