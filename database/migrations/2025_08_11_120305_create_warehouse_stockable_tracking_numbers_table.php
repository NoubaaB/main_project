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
        Schema::create('warehouse_stockable_tracking_numbers', function (Blueprint $table) {
            $table->id();

            $table->json("tracking_numbers")->nullable();

            $table->date("date_in_warehouse")->nullable();
            $table->date("date_out_of_warehouse")->nullable();


            $table->decimal("unit_price",15,2)->default(0.00);
            $table->decimal("total_weight",15,2)->default(0.00);
            $table->decimal("total_price",15,2)->default(0.00);
            $table->decimal("total_package_count",15,2)->default(0.00);

            $table->foreignId('warehouse_stockable_id')->nullable()->index("WS_IDX");
            $table->float('custom_wap')->nullable()
                ->comment('Manual override for Weighted Average Price');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This warehouse_stockable_tracking_numbers table describe the tracking numbers of fish types in warehouse_stockable table and have relationship with warehouse_stockable table as belong to one warehouse_stockable record and its source of warehouse_stockable.total_weight , warehouse_stockable.total_price , warehouse_stockable.total_package_count total calculation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stockable_tracking_numbers');
    }
};
