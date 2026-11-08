<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\DetailCharge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChargeCalculationService
{
    /**
     * Recalculate totals (total_weight and total_price) for all charges or a specific subset.
     *
     * @param array $options [
     *   'charge_ids' => int[]|null,
     *   'dry_run'    => bool,
     *   'fix_details'=> bool (whether to sync unit_price/total_price on details first),
     * ]
     * @return array Summary of the recalculation process
     */
    public function recalculate(array $options = []): array
    {
        $start = microtime(true);
        $dryRun = !empty($options['dry_run']);
        $chargeIds = !empty($options['charge_ids']) ? array_filter((array) $options['charge_ids']) : null;
        $fixDetails = $options['fix_details'] ?? true;

        $detailsFixed = [
            'total_price_fixed' => 0,
            'unit_price_fixed'  => 0,
        ];

        // Step 1: Ensure detail charges arithmetic integrity
        if ($fixDetails && !$dryRun) {
            $detailsFixed['total_price_fixed'] = $this->fixDetailTotalPrices($chargeIds);
            $detailsFixed['unit_price_fixed']  = $this->fixDetailUnitPrices($chargeIds);
        }

        // Step 2: Query charges that have detail charges and have total discrepancies
        $query = DB::table('charges as c')
            ->join('detail_charges as d', function ($join) {
                $join->on('d.charge_id', '=', 'c.id')
                    ->whereNull('d.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->select([
                'c.id',
                'c.date',
                'c.total_weight as old_weight',
                'c.total_weight_invoice as old_weight_invoice',
                'c.total_price as old_price',
                DB::raw('ROUND(COALESCE(SUM(d.weight), 0), 2) as calculated_weight'),
                DB::raw('ROUND(COALESCE(SUM(COALESCE(d.weight_invoice, d.weight, 0)), 0), 2) as calculated_weight_invoice'),
                DB::raw('ROUND(COALESCE(SUM(d.total_price), 0), 2) as calculated_price'),
                DB::raw('COUNT(d.id) as detail_count'),
            ])
            ->groupBy('c.id', 'c.date', 'c.total_weight', 'c.total_weight_invoice', 'c.total_price')
            ->havingRaw('(ABS(c.total_weight - calculated_weight) > 0.009 OR ABS(COALESCE(c.total_weight_invoice, 0) - calculated_weight_invoice) > 0.009 OR ABS(c.total_price - calculated_price) > 0.009)');

        if (!empty($chargeIds)) {
            $query->whereIn('c.id', $chargeIds);
        }

        $discrepancies = $query->get();
        $discrepantCount = $discrepancies->count();

        // Calculate differences
        $totalOldWeight = 0.0;
        $totalNewWeight = 0.0;
        $totalOldPrice  = 0.0;
        $totalNewPrice  = 0.0;
        $affectedIds    = [];
        $sampleChanges  = [];

        foreach ($discrepancies as $row) {
            $affectedIds[] = (int) $row->id;
            $oldW = (float) $row->old_weight;
            $newW = (float) $row->calculated_weight;
            $oldP = (float) $row->old_price;
            $newP = (float) $row->calculated_price;

            $totalOldWeight += $oldW;
            $totalNewWeight += $newW;
            $totalOldPrice  += $oldP;
            $totalNewPrice  += $newP;

            if (count($sampleChanges) < 15) {
                $sampleChanges[] = [
                    'id'             => (int) $row->id,
                    'date'           => $row->date,
                    'detail_count'   => (int) $row->detail_count,
                    'old_weight'     => $oldW,
                    'new_weight'     => $newW,
                    'diff_weight'    => round($newW - $oldW, 2),
                    'old_price'      => $oldP,
                    'new_price'      => $newP,
                    'diff_price'     => round($newP - $oldP, 2),
                ];
            }
        }

        $updatedCount = 0;

        // Step 3: Perform updates if not a dry-run
        if (!$dryRun && $discrepantCount > 0) {
            $updatedCount = DB::transaction(function () use ($affectedIds) {
                // Batch update charges joined with aggregated details
                $idList = implode(',', $affectedIds);
                return DB::update("
                    UPDATE charges c
                    JOIN (
                        SELECT 
                            d.charge_id,
                            ROUND(COALESCE(SUM(d.weight), 0), 2) AS calculated_weight,
                            ROUND(COALESCE(SUM(COALESCE(d.weight_invoice, d.weight, 0)), 0), 2) AS calculated_weight_invoice,
                            ROUND(COALESCE(SUM(d.total_price), 0), 2) AS calculated_price
                        FROM detail_charges d
                        WHERE d.deleted_at IS NULL
                          AND d.charge_id IN ({$idList})
                        GROUP BY d.charge_id
                    ) agg ON agg.charge_id = c.id
                    SET 
                        c.total_weight = agg.calculated_weight,
                        c.total_weight_invoice = agg.calculated_weight_invoice,
                        c.total_price = agg.calculated_price,
                        c.updated_at = NOW()
                    WHERE c.deleted_at IS NULL
                      AND c.id IN ({$idList})
                ");
            });

            Log::info("[ChargeCalculationService] Recalculated {$updatedCount} charges. Weight diff: " . ($totalNewWeight - $totalOldWeight) . " Kg, Price diff: " . ($totalNewPrice - $totalOldPrice) . " MAD.");
        }

        // Global metrics
        $globalTotals = DB::table('charges')
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as total_charges, ROUND(SUM(total_weight), 2) as global_weight, ROUND(SUM(total_price), 2) as global_price')
            ->first();

        $duration = round(microtime(true) - $start, 3);

        return [
            'success'               => true,
            'dry_run'               => $dryRun,
            'duration_seconds'      => $duration,
            'total_charges_in_db'   => (int) ($globalTotals->total_charges ?? 0),
            'global_total_weight'   => (float) ($globalTotals->global_weight ?? 0.0),
            'global_total_price'    => (float) ($globalTotals->global_price ?? 0.0),
            'discrepant_count'      => $discrepantCount,
            'updated_count'         => $dryRun ? 0 : $updatedCount,
            'affected_charge_ids'   => $affectedIds,
            'total_old_weight'      => round($totalOldWeight, 2),
            'total_new_weight'      => round($totalNewWeight, 2),
            'diff_weight'           => round($totalNewWeight - $totalOldWeight, 2),
            'total_old_price'       => round($totalOldPrice, 2),
            'total_new_price'       => round($totalNewPrice, 2),
            'diff_price'            => round($totalNewPrice - $totalOldPrice, 2),
            'details_fixed'         => $detailsFixed,
            'sample_changes'        => $sampleChanges,
        ];
    }

    /**
     * Recalculate a single charge by ID or model instance.
     */
    public function recalculateSingle(int|Charge $charge): array
    {
        $chargeModel = is_numeric($charge) ? Charge::with('detailCharges')->find($charge) : $charge;
        if (!$chargeModel) {
            return ['success' => false, 'message' => 'Charge not found'];
        }

        $activeDetails = $chargeModel->detailCharges()->whereNull('deleted_at')->get();
        if ($activeDetails->isEmpty()) {
            return [
                'success' => true,
                'updated' => false,
                'message' => 'Charge has no active detail lines; preserved stored values.',
                'total_weight' => (float) $chargeModel->total_weight,
                'total_price'  => (float) $chargeModel->total_price,
            ];
        }

        $calculatedWeight = round((float) $activeDetails->sum('weight'), 2);
        $calculatedWeightInvoice = round((float) $activeDetails->sum(function ($dc) {
            return $dc->weight_invoice !== null ? (float) $dc->weight_invoice : (float) $dc->weight;
        }), 2);
        $calculatedPrice  = round((float) $activeDetails->sum('total_price'), 2);

        $oldWeight = (float) $chargeModel->total_weight;
        $oldWeightInvoice = (float) $chargeModel->total_weight_invoice;
        $oldPrice  = (float) $chargeModel->total_price;

        $isDirty = (abs($oldWeight - $calculatedWeight) > 0.009 || abs($oldWeightInvoice - $calculatedWeightInvoice) > 0.009 || abs($oldPrice - $calculatedPrice) > 0.009);

        if ($isDirty) {
            $chargeModel->total_weight = $calculatedWeight;
            $chargeModel->total_weight_invoice = $calculatedWeightInvoice;
            $chargeModel->total_price  = $calculatedPrice;
            $chargeModel->save();
        }

        return [
            'success'               => true,
            'updated'               => $isDirty,
            'charge_id'             => $chargeModel->id,
            'old_weight'            => $oldWeight,
            'new_weight'            => $calculatedWeight,
            'old_weight_invoice'    => $oldWeightInvoice,
            'new_weight_invoice'    => $calculatedWeightInvoice,
            'diff_weight'           => round($calculatedWeight - $oldWeight, 2),
            'old_price'             => $oldPrice,
            'new_price'             => $calculatedPrice,
            'diff_price'            => round($calculatedPrice - $oldPrice, 2),
            'detail_count'          => $activeDetails->count(),
        ];
    }

    /**
     * Fix detail charges where weight and unit_price are set, but total_price is missing or zero.
     */
    protected function fixDetailTotalPrices(?array $chargeIds = null): int
    {
        $queryWeight = DB::table('detail_charges')
            ->whereNull('deleted_at')
            ->where('weight', '>', 0)
            ->where('unit_price', '>', 0)
            ->where(function ($q) {
                $q->whereNull('total_price')->orWhere('total_price', 0);
            });

        if (!empty($chargeIds)) {
            $queryWeight->whereIn('charge_id', $chargeIds);
        }

        $c1 = $queryWeight->update([
            'total_price' => DB::raw('ROUND(weight * unit_price, 2)'),
            'updated_at'  => now(),
        ]);

        $queryBox = DB::table('detail_charges')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('weight')->orWhere('weight', 0);
            })
            ->where('nbr_box', '>', 0)
            ->where('unit_price', '>', 0)
            ->where(function ($q) {
                $q->whereNull('total_price')->orWhere('total_price', 0);
            });

        if (!empty($chargeIds)) {
            $queryBox->whereIn('charge_id', $chargeIds);
        }

        $c2 = $queryBox->update([
            'total_price' => DB::raw('ROUND(nbr_box * unit_price, 2)'),
            'updated_at'  => now(),
        ]);

        // Fix missing weight_invoice
        $queryWi = DB::table('detail_charges')
            ->whereNull('deleted_at')
            ->where('weight', '>', 0)
            ->whereNull('weight_invoice');

        if (!empty($chargeIds)) {
            $queryWi->whereIn('charge_id', $chargeIds);
        }

        $queryWi->update([
            'weight_invoice' => DB::raw('weight'),
            'updated_at'     => now(),
        ]);

        return $c1 + $c2;
    }

    /**
     * Fix detail charges where weight and total_price are set, but unit_price is missing or zero.
     */
    protected function fixDetailUnitPrices(?array $chargeIds = null): int
    {
        $query = DB::table('detail_charges')
            ->whereNull('deleted_at')
            ->where('weight', '>', 0)
            ->where('total_price', '>', 0)
            ->where(function ($q) {
                $q->whereNull('unit_price')->orWhere('unit_price', 0);
            });

        if (!empty($chargeIds)) {
            $query->whereIn('charge_id', $chargeIds);
        }

        return $query->update([
            'unit_price' => DB::raw('ROUND(total_price / weight, 2)'),
            'updated_at' => now(),
        ]);
    }
}

