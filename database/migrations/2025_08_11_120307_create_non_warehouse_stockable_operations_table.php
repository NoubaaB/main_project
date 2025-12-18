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

            $table->date("date")->nullable();

            $table->decimal("unit_price",15,2)->default(0.00);
            $table->decimal("total_weight",15,2)->default(0.00);
            $table->decimal("total_weight_bill",15,2)->default(0.00);
            $table->decimal("total_price",15,2)->default(0.00);
            $table->decimal("total_package_count",15,2)->default(0.00);

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
