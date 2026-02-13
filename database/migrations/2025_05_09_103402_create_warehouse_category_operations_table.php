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
        Schema::create('warehouse_category_operations', function (Blueprint $table) {
            $table->id();

            $table->string("name_fr")->nullable();
            $table->string("name_en")->nullable();
            $table->string("name_es")->nullable();
            $table->string("description")->nullable();
            $table->boolean("allow_weight")->default(false);
            $table->boolean("allow_price")->default(true);
            $table->boolean("allow_package_type")->default(true);
            $table->boolean("allow_warehouse")->default(true);
            $table->boolean("allow_detail_truck_id")->default(false);

            $table->enum("operation_type",["charge","sells","both"])->default("both");
            $table->enum("warehouse_type",["FishType","GuanoType","PackageType"])->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This table stores categories for charge and sells \'s warehouse_stockable_tracking_number_operations, allowing for organized classification of various charge/sells items , its assosiated with tables warehouse_stockable_tracking_number_operations as has many of warehouse_stockable_tracking_number_operation records and assosiated with table non_warehouse_stockable_operations as has many of non_warehouse_stockable_operation records');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_category_operations');
    }
};
