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
        Schema::create('warehouse_stockable_tracking_number_operations', function (Blueprint $table) {
            $table->id();

            $table->enum("type_operation", ["in", "out"])->nullable()
                ->comment('this column describe the operation movement of warehouse_stockable_tracking_numbers table\'s');

            $table->date("date")->nullable();

            $table->decimal("unit_price",15,2)->default(0.00);
            $table->decimal("total_weight",15,2)->default(0.00);
            $table->decimal("total_weight_bill",15,2)->default(0.00);
            $table->decimal("total_price",15,2)->default(0.00);
            $table->decimal("total_package_count",15,2)->default(0.00);

            $table->foreignId('package_type_id')->nullable()->index("PT_IDX");
            $table->foreignId('warehouse_stockable_tracking_number_id')->nullable()->index("SFTN_IDX");
            $table->foreignId('warehouse_category_operation_id')->nullable()->index("WCO_IDX");
            $table->nullableMorphs('stockable',"STK_IDX"); //fish_type or package_type or guano_type

            $table->nullableMorphs('sourceable',"OP_IDX");

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This warehouse_stockable_tracking_number_operations table describe all charges detail\'s operations of tracking numbers of fish types in warehouse_stockable_tracking_numbers table what ever is IN or OUT depend of type_operation column, and have relationship with warehouse_stockable_tracking_numbers table as belong to one warehouse_stockable_tracking_numbers record and its source of warehouse_stockable_tracking_numbers.total_weight , warehouse_stockable_tracking_numbers.total_price , warehouse_stockable_tracking_numbers.total_package_count total calculation , and assoiciated with table charge_warehouse_categories as belong to one warehouse_category_operation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stockable_tracking_number_operations');
    }
};
