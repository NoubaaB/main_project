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
        Schema::create('sell_details', function (Blueprint $table) {
            $table->id();
            $table->double("weight")->default(0.00);
            $table->double("price")->default(0.00);
            $table->double("unit_price")->default(0.00);
            $table->double("package_count")->default(0.00);
            
            $table->foreignId('sell_id')->nullable()->index();
            $table->nullableMorphs('detailable');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This sell_details table describe each line in sell table and have relationship with charge_details table as belong to one charge_detail record , and its source of sells.sell_details_total_weight , sells.sell_details_total_price total calculation' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sell_details');
    }
};
