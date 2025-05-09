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
        Schema::create('fresh_fish_receives', function (Blueprint $table) {
            $table->id();
            $table->date("date");
            $table->json("tracking_numbers")->nullable();
            $table->double("total_price")->default(0.00);
            $table->double("total_weight")->default(0.00);
            $table->boolean("is_stockable")->default(false);

            $table->foreignId('primary_fish_detail_id')->nullable()->index();
            $table->foreignId('freeze_fish_process_id')->nullable()->index();
            $table->foreignId('fishery_id')->nullable()->index();
            $table->foreignId('origin_id')->nullable()->index();
            $table->foreignId('charge_type_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fresh_fish_receives');
    }
};
