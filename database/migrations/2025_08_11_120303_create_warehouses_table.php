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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string("name",50)->unique();
            $table->string("description",250)->nullable();
            $table->double("unit_price")->default(0.00);
            $table->double("total_weight")->default(0.00);
            $table->double("total_price")->default(0.00);
            $table->double("total_package_count")->default(0.00);
            $table->enum("warehouse_type", ["fish_type", "guano_type", "package_type"])->nullable();

            $table->foreignId('warehouse_category_id')->nullable()->index()->comment("This warehouse_category_id's Column is one to many relationship with warehouses table its describe type of wirehouse. local means every fish in the company store here , foreign means that fish that company store in other companies , client means fish that client store in our local warehouses");

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This warehouses table have relationship with warehouse_categories_table each warehouses table have one warehouse_categories that indicate type of wirehouse' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
