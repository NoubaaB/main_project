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
        Schema::create('stock_fish', function (Blueprint $table) {
            $table->id();

            $table->double("unit_price")->default(0.00);
            $table->double("total_weight")->default(0.00);
            $table->double("total_price")->default(0.00);
            $table->double("total_package_count")->default(0.00);

            $table->foreignId('stock_id')->nullable()->index();
            $table->foreignId('fish_type_id')->nullable()->index();

            $table->timestamps();    
            $table->softDeletes();

            $table->comment('This stock_fish table describe the fish types in stock table and have relationship with stocks table as belong to one stock record and its source of stocks.total_weight , stocks.total_price , stocks.total_package_count total calculation' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_fish');
    }
};
