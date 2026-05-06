<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Individual line items within a shipping (sale).
        Schema::create('detail_shippings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_id')->constrained()->cascadeOnDelete()->comment('FK to shippings - parent shipping');
            $table->foreignId('detail_shipping_category_id')->constrained('detail_shipping_categories')->cascadeOnDelete()->comment('FK to detail_shipping_categories');
            $table->text('article_description')->nullable()->comment('Free-text article description');
            $table->string('stockable_type')->nullable()->comment('Polymorphic type: FishType, GuanoType, or PackageType');
            $table->unsignedBigInteger('stockable_id')->nullable()->comment('Polymorphic ID');
            $table->decimal('weight', 14, 2)->nullable()->comment('Weight in KG');
            $table->decimal('unit_price', 14, 2)->nullable()->comment('Price per KG in MAD');
            $table->decimal('total_price', 14, 2)->nullable()->comment('Total line price in MAD');
            $table->foreignId('package_type_id')->nullable()->constrained('package_types')->nullOnDelete()->comment('FK to package_types');
            $table->decimal('nbr_box', 10, 2)->nullable()->comment('Number of boxes/packages');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete()->comment('FK to warehouses');
            $table->string('warehouse_operation')->nullable()->comment('stock_in or stock_out');
            $table->foreignId('truck_id')->nullable()->constrained('trucks')->nullOnDelete()->comment('FK to trucks');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Line items in a shipping. Polymorphic stockable. BelongsTo shippings, detail_shipping_categories, package_types, warehouses, trucks.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_shippings');
    }
};
