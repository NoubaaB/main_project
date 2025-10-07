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
        Schema::create('stock_detail_operations', function (Blueprint $table) {
            $table->id();
            $table->double("unit_price")->default(0.00);
            $table->double("weight")->default(0.00);
            $table->double("price")->default(0.00);

            $table->morphs('operatable');

            $table->foreignId('stock_fish_detail_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This stock_detail_operations table describe the operations on each line of stock_fish_details table its like log file of stock_fish_details table\'s');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_detail_operations');
    }
};
