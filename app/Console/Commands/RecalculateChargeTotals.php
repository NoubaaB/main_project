<?php

namespace App\Console\Commands;

use App\Services\ChargeCalculationService;
use Illuminate\Console\Command;

class RecalculateChargeTotals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'charges:recalculate-totals
                            {--charge-id= : Specific charge ID to recalculate}
                            {--dry-run : Preview changes without persisting to the database}
                            {--force : Force the operation without confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate total_weight and total_price of charges by accessing their detail charges';

    /**
     * Execute the console command.
     */
    public function handle(ChargeCalculationService $service)
    {
        $chargeId = $this->option('charge-id');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->info("========================================================");
        $this->info("   Jupiter ERP — Charge Totals Recalculation Engine      ");
        $this->info("========================================================");

        if ($dryRun) {
            $this->warn("MODE: DRY RUN (no database changes will be saved)\n");
        } elseif ($chargeId) {
            $this->info("MODE: Single Charge Recalculation (Charge #{$chargeId})\n");
        } else {
            $this->info("MODE: Full Database Synchronization\n");
            if (!$force && !$this->confirm("Are you sure you want to recalculate and synchronize totals for all charges?", true)) {
                $this->warn("Recalculation cancelled by user.");
                return Command::SUCCESS;
            }
        }

        $options = [
            'dry_run'    => $dryRun,
            'charge_ids' => $chargeId ? [(int) $chargeId] : null,
            'fix_details'=> true,
        ];

        $this->line("Analyzing charges and detail charges...");
        $result = $service->recalculate($options);

        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Charges in Database', number_format($result['total_charges_in_db'])],
                ['Charges with Discrepancies', number_format($result['discrepant_count'])],
                ['Charges Updated', number_format($result['updated_count'])],
                ['Previous Total Weight (affected)', number_format($result['total_old_weight'], 2) . ' Kg'],
                ['Calculated Total Weight (affected)', number_format($result['total_new_weight'], 2) . ' Kg'],
                ['Net Weight Adjustment', ($result['diff_weight'] >= 0 ? '+' : '') . number_format($result['diff_weight'], 2) . ' Kg'],
                ['Previous Total Price (affected)', number_format($result['total_old_price'], 2) . ' MAD'],
                ['Calculated Total Price (affected)', number_format($result['total_new_price'], 2) . ' MAD'],
                ['Net Price Adjustment', ($result['diff_price'] >= 0 ? '+' : '') . number_format($result['diff_price'], 2) . ' MAD'],
                ['Global Active Total Weight', number_format($result['global_total_weight'], 2) . ' Kg'],
                ['Global Active Total Price', number_format($result['global_total_price'], 2) . ' MAD'],
                ['Execution Time', $result['duration_seconds'] . 's'],
            ]
        );

        if (!empty($result['sample_changes'])) {
            $this->newLine();
            $this->info("Top Discrepant Charges Sample:");
            $sampleRows = array_map(function ($s) {
                return [
                    '#' . $s['id'],
                    $s['date'],
                    $s['detail_count'],
                    number_format($s['old_weight'], 2) . ' Kg',
                    number_format($s['new_weight'], 2) . ' Kg',
                    ($s['diff_weight'] >= 0 ? '+' : '') . number_format($s['diff_weight'], 2) . ' Kg',
                    number_format($s['old_price'], 2) . ' MAD',
                    number_format($s['new_price'], 2) . ' MAD',
                    ($s['diff_price'] >= 0 ? '+' : '') . number_format($s['diff_price'], 2) . ' MAD',
                ];
            }, $result['sample_changes']);

            $this->table(
                ['Charge ID', 'Date', 'Lines', 'Old Weight', 'New Weight', 'Weight Diff', 'Old Price', 'New Price', 'Price Diff'],
                $sampleRows
            );
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn("Dry run complete. No changes were applied. Run without --dry-run to apply updates.");
        } else {
            $this->newLine();
            $this->info("Recalculation complete. {$result['updated_count']} charges successfully synchronized with detail charges.");
        }

        return Command::SUCCESS;
    }
}

