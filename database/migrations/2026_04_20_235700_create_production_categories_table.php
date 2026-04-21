<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('name_fr')->nullable();
            $table->string('name_en')->nullable();
            $table->string('name_es')->nullable();
            $table->string('description')->nullable();
            $table->boolean('enable_reception_tracking_number')->default(false);
            $table->boolean('enable_tracking_onp_numbers')->default(false);
            $table->boolean('enable_production_tracking_numbers')->default(false);
            $table->boolean('enable_fishery_id')->default(false);
            $table->boolean('enable_ship_id')->default(false);
            $table->boolean('enable_origin_id')->default(false);
            $table->boolean('enable_truck_id')->default(false);
            $table->boolean('enable_supplier_process_id')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_categories');
    }
};
