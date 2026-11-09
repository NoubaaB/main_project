<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Warehouse balances track stock entries using FIFO methodology.
        // Each row represents one stock_in or stock_out entry with remaining weight tracking.
        Schema::create('warehouse_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('FK to warehouses');
            $table->string('stockable_type')->comment('Polymorphic type: FishType, GuanoType, or PackageType');
            $table->unsignedBigInteger('stockable_id')->comment('Polymorphic ID of the stocked item');
            $table->string('reception_tracking_number')->nullable()->comment('Tracking number from the source charge/production');
            $table->enum('warehouse_operation', ['stock_in', 'stock_out'])->comment('stock_in or stock_out');
            $table->date('date')->comment('Date of the stock operation');
            $table->decimal('total_weight', 14, 2)->default(0)->comment('Original total weight entered in KG');
            $table->decimal('remaining_weight', 14, 2)->default(0)->comment('Current remaining weight after withdrawals (FIFO)');
            $table->decimal('total_price', 14, 2)->default(0)->comment('Total price of the stock entry in MAD');
            $table->decimal('unit_price', 14, 2)->default(0)->comment('Unit price per KG in MAD');
            $table->decimal('package_count', 10, 2)->default(0)->comment('Number of packages/boxes');
            $table->foreignId('package_type_id')->nullable()->constrained('package_types')->nullOnDelete()->comment('FK to package_types');
            $table->string('supplier_name')->nullable()->comment('Cached supplier name for display');
            $table->string('origin_name')->nullable()->comment('Cached origin name for display');
            $table->string('fishery_name')->nullable()->comment('Cached fishery name for display');
            $table->string('client_name')->nullable()->comment('Cached client name for display');
            $table->string('source_type')->nullable()->comment('Polymorphic source: DetailCharge, DetailSell, or DetailProduction');
            $table->unsignedBigInteger('source_id')->nullable()->comment('Polymorphic ID of the source detail record');
            $table->boolean('is_finished')->default(false)->comment('True when remaining_weight <= 0 (fully consumed)');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'stockable_type', 'stockable_id'], 'wb_wh_stockable');
            $table->index('reception_tracking_number', 'wb_tracking');
            $table->index('is_finished', 'wb_finished');
            $table->index(['warehouse_id', 'warehouse_operation', 'is_finished', 'remaining_weight'], 'idx_wb_wh_op_finished');
            $table->index(['warehouse_operation', 'is_finished', 'remaining_weight', 'date'], 'idx_wb_op_finished_date');
            $table->comment('Stock entries per warehouse (FIFO). Polymorphic stockable and source. BelongsTo warehouses, package_types. Has many warehouse_balance_operations.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_balances');
    }
};

