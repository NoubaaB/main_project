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
        Schema::create('production_details', function (Blueprint $table) {
            $table->id();
            $table->double("weight")->default(0.00);
            $table->double("price")->default(0.00);
            $table->double("unit_price")->default(0.00);
            $table->double("package_count")->default(0.00);

            $table->foreignId('fish_type_id')->nullable()->index();
            $table->foreignId('stock_id')->nullable()->index();
            $table->foreignId('charge_id')->nullable()->index();
            $table->foreignId('package_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This production_details table describe each line in charge table and have relationship with fish_types table as belong to one fish_type record, packages table as belong to one package record, stocks table as belong to one stock record, charges table as belong to one charge record , and its source of charges.total_weight , charges.total_price total calculation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_details');
    }
};
