<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Physical storage locations for fish, by-products, and packaging.
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Warehouse name');
            $table->text('description')->nullable()->comment('Description');
            $table->foreignId('warehouse_category_id')->nullable()->constrained('warehouse_categories')->nullOnDelete()->comment('FK to warehouse_categories');
            $table->string('warehouse_type')->default('FishType')->comment('Type of goods stored: FishType, GuanoType, or PackageType');
            $table->boolean('has_sejour')->default(false)->comment('Whether this warehouse charges daily storage fees (sejour)');
            $table->decimal('operation_service_fee', 12, 2)->default(0)->comment('Fixed service fee per operation in MAD');
            $table->integer('wap_days')->default(30)->comment('Number of days for WAP (Weighted Average Price) calculation');
            $table->integer('wap_sells_count')->default(3)->comment('Number of recent sells used for WAP calculation');
            $table->boolean('is_allow_price')->default(true)->comment('Whether price tracking is enabled for this warehouse');
            $table->decimal('total_weight', 14, 2)->default(0)->comment('Current total stock weight in KG (computed)');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Storage locations. BelongsTo warehouse_categories. warehouse_type=FishType/GuanoType/PackageType. Has many warehouse_balances, sejour_rates.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
