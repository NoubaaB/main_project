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
        Schema::create('non_warehouse_stockable_operations', function (Blueprint $table) {
            $table->id();

            $table->double("unit_price")->default(0.00);
            $table->double("total_weight")->default(0.00);
            $table->double("total_weight_bill")->default(0.00);
            $table->double("total_price")->default(0.00);
            $table->double("total_package_count")->default(0.00);

            $table->foreignId('charge_id')->nullable()->index();
            $table->foreignId('package_type_id')->nullable()->index();
            $table->foreignId('warehouse_category_operation_id')->nullable()->index("CWC_IDX");
            $table->nullableMorphs('stockable', "STK_IDX"); //fish_type or package_type or guano_type
            
            $table->timestamps();
            $table->softDeletes();

            $table->comment("This non_warehouse_stockable_operations table is describe all charges detail items that are not regularly kept in a company's warehouse or inventory , its can be fishs or services or other fish production charges , and assoiciated with table charge_warehouse_categories as belong to one warehouse_category_operation");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_warehouse_stockable_operations');
    }
};
