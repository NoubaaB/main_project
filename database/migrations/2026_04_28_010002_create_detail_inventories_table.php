<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Individual line items within an inventory count.
        Schema::create('detail_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete()->comment('FK to inventories - parent inventory');
            $table->foreignId('detail_inventory_category_id')->nullable()->constrained('detail_inventory_categories')->cascadeOnDelete()->comment('FK to detail_inventory_categories');
            $table->string('article_description')->nullable()->comment('Free-text article description');
            $table->string('stockable_type')->nullable()->comment('Polymorphic type: FishType, GuanoType, or PackageType');
            $table->unsignedBigInteger('stockable_id')->nullable()->comment('Polymorphic ID');
            $table->decimal('weight', 14, 2)->nullable()->comment('Counted weight in KG');
            $table->decimal('unit_price', 14, 2)->nullable()->comment('Unit price in MAD');
            $table->decimal('total_price', 14, 2)->nullable()->comment('Total value in MAD');
            $table->foreignId('package_type_id')->nullable()->constrained('package_types')->nullOnDelete()->comment('FK to package_types');
            $table->decimal('nbr_box', 14, 2)->nullable()->comment('Number of boxes/packages');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete()->comment('FK to warehouses');
            $table->string('warehouse_operation')->nullable()->comment('stock_in or stock_out');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Line items in an inventory count. Polymorphic stockable. BelongsTo inventories, detail_inventory_categories, package_types, warehouses.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_inventories');
    }
};
