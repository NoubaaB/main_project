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
            $table->boolean("is_stockable")->default(false);

            $table->foreignId('fishery_id')->nullable()->index();
            $table->foreignId('origin_id')->nullable()->index();
            $table->foreignId('type_charge_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This charges table describe purchase fish or other type of charges and caclulate total of price and quantity in stock_fish_tracking_number_operations.total_price , stock_fish_tracking_number_operations.total_weight and have relationship with suppliers table as belong to one supplier record, fisheries table as belong to one fishery record, origins table as belong to one origin record, type_charges table as belong to one type_charge record, stock_fish_tracking_number_operations table as has many of stock_fish_tracking_number_operations records' );
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
