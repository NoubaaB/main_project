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
        Schema::create('stock_details', function (Blueprint $table) {
            $table->id();
            $table->double("unit_price")->default(0.00);
            $table->double("total_weight")->default(0.00);
            $table->double("total_price")->default(0.00);

            $table->foreignId('stock_id')->nullable()->index();
            $table->foreignId('fish_type_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This stock_details table describe the details of each stock such as total weight , total price and pu of each fish type in each stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_details');
    }
};
