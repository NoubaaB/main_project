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
        Schema::create('stock_fish_details', function (Blueprint $table) {
            $table->id();
            $table->double("unit_price")->default(0.00);
            $table->double("weight")->default(0.00);
            $table->double("price")->default(0.00);

            $table->morphs('detailable');

            $table->foreignId('stock_detail_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This stock_fish_details table describe the fish details in each line of stock_details table');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_fish_details');
    }
};
