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
        Schema::create('warehouse_stockables', function (Blueprint $table) {
            $table->id();

            $table->decimal("unit_price",15,2)->default(0.00);
            $table->decimal("total_weight",15,2)->default(0.00);
            $table->decimal("total_price",15,2)->default(0.00);
            $table->decimal("total_package_count",15,2)->default(0.00);

            $table->foreignId('warehouse_id')->nullable()->index();
            $table->nullableMorphs('stockable'); //fish_type or package

            $table->timestamps();    
            $table->softDeletes();

            $table->comment('This warehouse_stockables table describe the fish types in warehouse table and have relationship with warehouses table as belong to one warehouse record and its source of warehouses.total_weight , warehouses.total_price , warehouses.total_package_count total calculation' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stockables');
    }
};
