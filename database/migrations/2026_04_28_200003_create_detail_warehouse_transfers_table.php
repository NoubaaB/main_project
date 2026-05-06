<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Individual line items within a warehouse transfer.
        Schema::create('detail_warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_transfer_id')->constrained('warehouse_transfers')->cascadeOnDelete()->comment('FK to warehouse_transfers - parent transfer');
            $table->foreignId('warehouse_transfer_category_id')->nullable()->constrained('warehouse_transfer_categories', 'id', 'dwt_category_fk')->nullOnDelete()->comment('FK to warehouse_transfer_categories');
            $table->string('stockable_type')->nullable()->comment('Polymorphic type: FishType, GuanoType, or PackageType');
            $table->unsignedBigInteger('stockable_id')->nullable()->comment('Polymorphic ID');
            $table->decimal('weight', 14, 2)->nullable()->comment('Weight transferred in KG');
            $table->foreignId('source_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete()->comment('FK to warehouses - origin warehouse for the transfer');
            $table->string('source_tracking_number')->nullable()->comment('Tracking number from the source warehouse balance');
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete()->comment('FK to warehouses - destination warehouse for the transfer');
            $table->foreignId('package_type_id')->nullable()->constrained('package_types')->nullOnDelete()->comment('FK to package_types');
            $table->decimal('nbr_box', 10, 2)->nullable()->comment('Number of boxes/packages');
            $table->foreignId('truck_id')->nullable()->constrained('trucks')->nullOnDelete()->comment('FK to trucks');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Line items in a transfer. Polymorphic stockable. BelongsTo warehouse_transfers, warehouse_transfer_categories, source/dest warehouses, package_types, trucks.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_warehouse_transfers');
    }
};
