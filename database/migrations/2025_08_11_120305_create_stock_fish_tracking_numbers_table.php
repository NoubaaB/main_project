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
        Schema::create('stock_fish_tracking_numbers', function (Blueprint $table) {
            $table->id();

            $table->json("tracking_numbers")->nullable();

            $table->date("date_in_stock")->nullable();
            $table->date("date_out_of_stock")->nullable();


            $table->double("unit_price")->default(0.00);
            $table->double("total_weight")->default(0.00);
            $table->double("total_price")->default(0.00);
            $table->double("total_package_count")->default(0.00);

            $table->foreignId('stock_fish_id')->nullable()->index();
            $table->foreignId('fish_type_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This stock_fish_tracking_numbers table describe the tracking numbers of fish types in stock_fish table and have relationship with stock_fish table as belong to one stock_fish record and its source of stock_fish.total_weight , stock_fish.total_price , stock_fish.total_package_count total calculation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_fish_tracking_numbers');
    }
};
