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
        Schema::create('warehouse_fish_tracking_numbers', function (Blueprint $table) {
            $table->id();

            $table->json("tracking_numbers")->nullable();

            $table->date("date_in_warehouse")->nullable();
            $table->date("date_out_of_warehouse")->nullable();


            $table->double("unit_price")->default(0.00);
            $table->double("total_weight")->default(0.00);
            $table->double("total_price")->default(0.00);
            $table->double("total_package_count")->default(0.00);

            $table->foreignId('warehouse_fish_id')->nullable()->index();
            $table->foreignId('fish_type_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This warehouse_fish_tracking_numbers table describe the tracking numbers of fish types in warehouse_fish table and have relationship with warehouse_fish table as belong to one warehouse_fish record and its source of warehouse_fish.total_weight , warehouse_fish.total_price , warehouse_fish.total_package_count total calculation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_fish_tracking_numbers');
    }
};
