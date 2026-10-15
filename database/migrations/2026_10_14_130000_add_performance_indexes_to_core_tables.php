<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            'charges' => ['idx_charges_date' => ['date']],
            'shippings' => ['idx_shippings_date' => ['date']],
            'productions' => ['idx_productions_date' => ['date']],
            'detail_charges' => ['idx_detail_charges_stockable' => ['stockable_type', 'stockable_id']],
            'detail_shippings' => ['idx_detail_shippings_stockable' => ['stockable_type', 'stockable_id']],
            'detail_productions' => ['idx_detail_productions_stockable' => ['stockable_type', 'stockable_id']],
            'fortnight_activities' => ['idx_fortnight_activities_date' => ['date']],
            'warehouse_balances' => [
                'idx_wb_wh_op_finished' => ['warehouse_id', 'warehouse_operation', 'is_finished', 'remaining_weight'],
                'idx_wb_op_finished_date' => ['warehouse_operation', 'is_finished', 'remaining_weight', 'date'],
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (!Schema::hasTable($table)) continue;
            foreach ($tableIndexes as $indexName => $columns) {
                try {
                    Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                        $t->index($columns, $indexName);
                    });
                } catch (\Throwable $e) {
                    // Index already exists, ignore
                }
            }
        }
    }

    public function down(): void
    {
        $indexes = [
            'charges' => ['idx_charges_date'],
            'shippings' => ['idx_shippings_date'],
            'productions' => ['idx_productions_date'],
            'detail_charges' => ['idx_detail_charges_stockable'],
            'detail_shippings' => ['idx_detail_shippings_stockable'],
            'detail_productions' => ['idx_detail_productions_stockable'],
            'fortnight_activities' => ['idx_fortnight_activities_date'],
            'warehouse_balances' => ['idx_wb_wh_op_finished', 'idx_wb_op_finished_date'],
        ];

        foreach ($indexes as $table => $indexNames) {
            if (!Schema::hasTable($table)) continue;
            foreach ($indexNames as $indexName) {
                try {
                    Schema::table($table, function (Blueprint $t) use ($indexName) {
                        $t->dropIndex($indexName);
                    });
                } catch (\Throwable $e) {
                    // Ignore
                }
            }
        }
    }
};
