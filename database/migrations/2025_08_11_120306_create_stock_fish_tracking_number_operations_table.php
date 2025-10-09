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
        Schema::create('stock_fish_tracking_number_operations', function (Blueprint $table) {
            $table->id();

            $table->enum("type_operation", ["in", "out"])->nullable()
                ->comment('this column describe the operation movement of stock_fish_tracking_numbers table\'s');

            $table->double("unit_price")->default(0.00);
            $table->double("total_weight")->default(0.00);
            $table->double("total_price")->default(0.00);
            $table->double("total_package_count")->default(0.00);

            $table->foreignId('package_id')->nullable()->index();
            $table->foreignId('stock_fish_tracking_number_id')->nullable()->index("SFTN_IDX");
            $table->foreignId('fish_type_id')->nullable()->index();

            $table->nullableMorphs('operationable',"OP_IDX");

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This stock_fish_tracking_number_operations table describe the operations of tracking numbers of fish types in stock_fish_tracking_numbers table what ever is IN or OUT depend of type_operation column, and have relationship with stock_fish_tracking_numbers table as belong to one stock_fish_tracking_numbers record and its source of stock_fish_tracking_numbers.total_weight , stock_fish_tracking_numbers.total_price , stock_fish_tracking_numbers.total_package_count total calculation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_fish_tracking_number_operations');
    }
};
