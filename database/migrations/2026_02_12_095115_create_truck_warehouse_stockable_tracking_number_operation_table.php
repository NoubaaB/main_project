<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('truck_warehouse_stockable_tracking_number_operation', function (Blueprint $table) {
            // columns (match the PK type of referenced tables)
            $table->unsignedBigInteger('truck_id');
            $table->unsignedBigInteger('warehouse_stockable_tracking_number_operation_id');

            // short indexes (optional but useful)
            $table->index('truck_id', 'idx_twstno_truck');
            $table->index('warehouse_stockable_tracking_number_operation_id', 'idx_twstno_wstno');

            // explicit foreign keys with short names (keeps identifier < 64 chars)
            $table->foreign('truck_id', 'fk_twstno_truck')
                ->references('id')->on('trucks')->onDelete('cascade');

            $table->foreign(
                'warehouse_stockable_tracking_number_operation_id',
                'fk_twstno_wstno'
            )->references('id')->on('warehouse_stockable_tracking_number_operations')->onDelete('cascade');

            // optional: composite primary key for pivot-like behaviour
            // $table->primary(['truck_id', 'warehouse_stockable_tracking_number_operation_id'], 'pk_twstno');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truck_warehouse_stockable_tracking_number_operation');
    }
};
