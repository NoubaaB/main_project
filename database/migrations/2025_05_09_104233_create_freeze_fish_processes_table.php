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
        Schema::create('freeze_fish_processes', function (Blueprint $table) {
            $table->id();
            $table->date("date");
            $table->json("tracking_numbers")->nullable();
            $table->double("total_price")->default(0.00);
            $table->double("total_weight")->default(0.00);
            $table->double("total_price_service")->default(0.00); // Service congelation charges
            $table->boolean("is_stockable")->default(true);
            $table->boolean("production_locally")->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freeze_fish_processes');
    }
};
