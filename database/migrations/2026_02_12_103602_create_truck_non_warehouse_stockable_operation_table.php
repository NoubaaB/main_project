<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('truck_non_warehouse_stockable_operation', function (Blueprint $table) {
            // columns (match the PK type of referenced tables)
            $table->unsignedBigInteger('truck_id');
            $table->unsignedBigInteger('non_warehouse_stockable_operation_id');

            // short indexes (optional but useful)
            $table->index('truck_id', 'idx_ntwstno_truck');
            $table->index('non_warehouse_stockable_operation_id', 'idx_ntwstno_wstno');

            // explicit foreign keys with short names (keeps identifier < 64 chars)
            $table->foreign('truck_id', 'fk_ntwstno_truck')
                ->references('id')->on('trucks')->onDelete('cascade');

            $table->foreign(
                'non_warehouse_stockable_operation_id',
                'fk_ntwstno_wstno'
            )->references('id')->on('non_warehouse_stockable_operations')->onDelete('cascade');

            // optional: composite primary key for pivot-like behaviour
            // $table->primary(['truck_id', 'non_warehouse_stockable_operation_id'], 'pk_ntwstno');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('truck_non_warehouse_stockable_operation');
    }
};
