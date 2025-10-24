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
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->date("date")->nullable();
            $table->json("tracking_numbers")->nullable();
            $table->json("tracking_onp_numbers")->nullable();
            
            $table->string("note",500)->nullable();
            $table->double("total_price")->default(0.00);
            $table->double("total_weight")->default(0.00);
            $table->double("total_package_count")->default(0.00);
            // $table->boolean("is_stockable")->default(false);

            $table->foreignId('fishery_id')->nullable()->index();
            $table->foreignId('ship_id')->nullable()->index();
            $table->foreignId('origin_id')->nullable()->index();
            $table->foreignId('type_charge_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This table records fish purchases. It calculates total price and weight in both tables stock_fish_tracking_number_operations and non_warehouse_charge_operations. It belongs to one supplier, one fishery, one origin, and one type_charge, and has many related with stock_fish_tracking_number_operations records and non_warehouse_charge_operations as well.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
