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
        Schema::create('non_warehouse_charge_items', function (Blueprint $table) {
            $table->id();


            $table->string("description")->nullable();
            $table->double("unit_price")->default(0.00);
            $table->double("total_weight")->default(0.00);
            $table->double("total_price")->default(0.00);
            $table->double("total_package_count")->default(0.00);

            $table->foreignId('charge_id')->nullable()->index();
            $table->foreignId('package_id')->nullable()->index();
            $table->foreignId('fish_type_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->comment("This non_warehouse_charge_items table is describe all charges detail items that are not regularly kept in a company's warehouse or inventory , its can be fishs or services or other fish production charges");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_warehouse_charge_items');
    }
};
