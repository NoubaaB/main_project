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
        Schema::create('production_fish_products', function (Blueprint $table) {
            $table->id();
            $table->date("date");
            $table->array("tracker_numbers");
            $table->double("total_price")->default(0.00);
            $table->double("total_weight")->default(0.00);
            $table->boolean("is_stockable")->default(true);

            $table->foreignId('production_fish_detail_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_fish_products');
    }
};
