<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            'inventories' => ['idx_inventories_date' => ['date']],
            'detail_inventories' => ['idx_detail_inventories_stockable' => ['stockable_type', 'stockable_id']],
            'warehouse_transfers' => [
                'idx_warehouse_transfers_date' => ['date'],
                'idx_warehouse_transfers_from_wh' => ['from_warehouse_id'],
                'idx_warehouse_transfers_to_wh' => ['to_warehouse_id'],
            ],
            'supplier_invoices' => [
                'idx_supplier_invoices_date' => ['date'],
                'idx_supplier_invoices_created_at' => ['created_at'],
            ],
            'client_invoices' => [
                'idx_client_invoices_date' => ['date'],
                'idx_client_invoices_created_at' => ['created_at'],
                'idx_client_invoices_type' => ['type_invoice'],
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
                    // Ignore if already exists
                }
            }
        }
    }

    public function down(): void
    {
        $indexes = [
            'inventories' => ['idx_inventories_date'],
            'detail_inventories' => ['idx_detail_inventories_stockable'],
            'warehouse_transfers' => ['idx_warehouse_transfers_date', 'idx_warehouse_transfers_from_wh', 'idx_warehouse_transfers_to_wh'],
            'supplier_invoices' => ['idx_supplier_invoices_date', 'idx_supplier_invoices_created_at'],
            'client_invoices' => ['idx_client_invoices_date', 'idx_client_invoices_created_at', 'idx_client_invoices_type'],
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
