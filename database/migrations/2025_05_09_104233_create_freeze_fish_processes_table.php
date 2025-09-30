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
            $table->date("date")->nullable();
            $table->json("tracking_numbers")->nullable();
            $table->json("tracking_onp_numbers")->nullable();
            $table->string("note", 500)->nullable();
            $table->double("total_price")->default(0.00);
            $table->double("total_weight")->default(0.00);
            $table->boolean("is_stockable")->default(false);

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
        Schema::dropIfExists('freeze_fish_processes');
    }
};
