<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Individual line items within a charge (purchase). Each line represents one product entry.
        Schema::create('detail_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')->constrained()->cascadeOnDelete()->comment('FK to charges - parent charge');
            $table->foreignId('detail_charge_category_id')->constrained('detail_charge_categories')->cascadeOnDelete()->comment('FK to detail_charge_categories - determines field visibility');
            $table->text('article_description')->nullable()->comment('Free-text article description (when article_type=text)');
            $table->string('stockable_type')->nullable()->comment('Polymorphic type: FishType, GuanoType, or PackageType');
            $table->unsignedBigInteger('stockable_id')->nullable()->comment('Polymorphic ID referencing fish_types, guano_types, or package_types');
            $table->decimal('weight', 14, 2)->nullable()->comment('Weight in KG');
            $table->decimal('unit_price', 14, 2)->nullable()->comment('Price per KG in MAD');
            $table->decimal('total_price', 14, 2)->nullable()->comment('Total line price in MAD');
            $table->decimal('weight_invoice', 14, 2)->nullable()->comment('Weight as shown on the invoice (may differ from actual weight)');
            $table->foreignId('package_type_id')->nullable()->constrained('package_types')->nullOnDelete()->comment('FK to package_types');
            $table->decimal('nbr_box', 10, 2)->nullable()->comment('Number of boxes/packages');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete()->comment('FK to warehouses - target warehouse for stock operation');
            $table->string('warehouse_operation')->nullable()->comment('stock_in or stock_out - direction of warehouse movement');
            $table->foreignId('truck_id')->nullable()->constrained('trucks')->nullOnDelete()->comment('FK to trucks - specific truck for this line');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()->comment('FK to suppliers - specific supplier for this line');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Line items in a charge. Polymorphic stockable (FishType/GuanoType/PackageType). BelongsTo charges, detail_charge_categories, package_types, warehouses, trucks, suppliers.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_charges');
    }
};
