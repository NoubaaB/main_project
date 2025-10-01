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
        Schema::create('charge_details', function (Blueprint $table) {
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charge_details');
    }
};
