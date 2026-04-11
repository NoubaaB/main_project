<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\FishProcessType;
use App\Models\FishType;
use App\Models\WarehouseCategoryOperation;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;

class ProductionYieldController extends Controller
{
    /**
     * Calculate production yield (rendement) as time-series grouped by fish process type.
     *
     * Query params:
     *   date_start, date_end, period (daily|monthly|yearly)
     *
     * Returns time-series: for each date bucket × process type → frais, congele, yield
     */
    public function getYield(): JsonResponse
    {
        $date_start = request()->query('date_start');
        $date_end   = request()->query('date_end');
        $period     = request()->query('period', 'daily'); // daily | monthly | yearly

        // Get production/reception warehouse_category_operation IDs
        $productionWcoIds = WarehouseCategoryOperation::where('is_production', true)
            ->where('warehouse_type', 'FishType')
            ->pluck('id')
            ->toArray();

        $receptionWcoIds = WarehouseCategoryOperation::where('is_production', false)
            ->where('warehouse_type', 'FishType')
            ->pluck('id')
            ->toArray();

        // Pre-load fish_process_type_id map
        $fishTypeProcessMap = FishType::whereNotNull('fish_process_type_id')
            ->pluck('fish_process_type_id', 'id')
            ->toArray();

        // Get all production charges in the date range
        $charges = Charge::whereBetween('date', [$date_start, $date_end])
            ->whereHas('charge_category', fn($q) => $q->where('is_production', true))
            ->with(['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations'])
            ->orderBy('date')
            ->get();

        // Build raw data: date → process_type_id → { frais, congele }
        $rawData = [];

        foreach ($charges as $charge) {
            $dateKey = $this->getDateKey($charge->date, $period);

            $allOps = array_merge(
                $charge->warehouse_stockable_tracking_number_operations->toArray(),
                $charge->non_warehouse_stockable_operations->toArray()
            );

            foreach ($allOps as $op) {
                if (($op['stockable_type'] ?? '') !== 'FishType') continue;

                $processTypeId = $fishTypeProcessMap[$op['stockable_id']] ?? null;
                if (!$processTypeId) continue;

                $wcoId = $op['warehouse_category_operation_id'] ?? null;

                if (!isset($rawData[$dateKey])) {
                    $rawData[$dateKey] = [];
                }
                if (!isset($rawData[$dateKey][$processTypeId])) {
                    $rawData[$dateKey][$processTypeId] = ['frais' => 0, 'congele' => 0];
                }

                $weight = $op['total_weight'] ?? 0;

                if (in_array($wcoId, $productionWcoIds)) {
                    $rawData[$dateKey][$processTypeId]['congele'] += $weight;
                } elseif (in_array($wcoId, $receptionWcoIds)) {
                    $rawData[$dateKey][$processTypeId]['frais'] += $weight;
                }
            }
        }

        // Build date range (all buckets, even if empty)
        $dates = $this->generateDateBuckets($date_start, $date_end, $period);

        // Collect all process type IDs present in data
        $allProcessTypeIds = [];
        foreach ($rawData as $dateData) {
            foreach (array_keys($dateData) as $ptId) {
                $allProcessTypeIds[$ptId] = true;
            }
        }
        $allProcessTypeIds = array_keys($allProcessTypeIds);

        // Load process type names
        $processTypes = FishProcessType::whereIn('id', $allProcessTypeIds)->get()->keyBy('id');

        // Build series per process type
        $series = [];
        foreach ($allProcessTypeIds as $ptId) {
            $ptName = $processTypes[$ptId]->name ?? 'Unknown';
            $fraisData = [];
            $congeleData = [];
            $yieldData = [];

            foreach ($dates as $dateKey) {
                $frais   = round($rawData[$dateKey][$ptId]['frais'] ?? 0, 2);
                $congele = round($rawData[$dateKey][$ptId]['congele'] ?? 0, 2);
                $yield   = $frais > 0 ? round(($congele / $frais) * 100, 2) : 0;

                $fraisData[]   = $frais;
                $congeleData[] = $congele;
                $yieldData[]   = $yield;
            }

            $series[] = [
                'id'              => $ptId,
                'name'            => $ptName,
                'frais_weights'   => $fraisData,
                'congele_weights' => $congeleData,
                'yields'          => $yieldData,
            ];
        }

        return response()->json([
            'dates'     => $dates,
            'series'    => $series,
            'period'    => $period,
        ], 200);
    }

    /**
     * Convert a date to a bucket key based on the period.
     */
    private function getDateKey($date, string $period): string
    {
        $carbon = Carbon::parse($date);
        return match ($period) {
            'monthly' => $carbon->format('Y-m'),
            'yearly'  => $carbon->format('Y'),
            default   => $carbon->format('Y-m-d'), // daily
        };
    }

    /**
     * Generate all date bucket keys for the given range.
     */
    private function generateDateBuckets(string $start, string $end, string $period): array
    {
        $startDate = Carbon::parse($start);
        $endDate   = Carbon::parse($end);
        $buckets   = [];

        switch ($period) {
            case 'monthly':
                $cursor = $startDate->copy()->startOfMonth();
                while ($cursor->lte($endDate)) {
                    $buckets[] = $cursor->format('Y-m');
                    $cursor->addMonth();
                }
                break;

            case 'yearly':
                $cursor = $startDate->copy()->startOfYear();
                while ($cursor->lte($endDate)) {
                    $buckets[] = $cursor->format('Y');
                    $cursor->addYear();
                }
                break;

            default: // daily
                $carbonPeriod = CarbonPeriod::create($startDate, $endDate);
                foreach ($carbonPeriod as $day) {
                    $buckets[] = $day->format('Y-m-d');
                }
                break;
        }

        return $buckets;
    }
}
