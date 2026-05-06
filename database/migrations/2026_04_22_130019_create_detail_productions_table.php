<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Individual line items within a production.
        Schema::create('detail_productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete()->comment('FK to productions - parent production');
            $table->foreignId('detail_production_category_id')->constrained('detail_production_categories')->cascadeOnDelete()->comment('FK to detail_production_categories');
            $table->text('article_description')->nullable()->comment('Free-text article description');
            $table->string('stockable_type')->nullable()->comment('Polymorphic type: FishType, GuanoType, or PackageType');
            $table->unsignedBigInteger('stockable_id')->nullable()->comment('Polymorphic ID');
            $table->decimal('weight', 14, 2)->nullable()->comment('Weight in KG');
            $table->decimal('unit_price', 14, 2)->nullable()->comment('Price per KG in MAD');
            $table->decimal('total_price', 14, 2)->nullable()->comment('Total line price in MAD');
            $table->foreignId('package_type_id')->nullable()->constrained('package_types')->nullOnDelete()->comment('FK to package_types');
            $table->decimal('nbr_box', 10, 2)->nullable()->comment('Number of boxes/packages');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete()->comment('FK to warehouses');
            $table->enum('warehouse_operation', ['stock_in', 'stock_out'])->nullable()->comment('stock_in or stock_out direction');
            $table->foreignId('truck_id')->nullable()->constrained('trucks')->nullOnDelete()->comment('FK to trucks');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Line items in a production. Polymorphic stockable. BelongsTo productions, detail_production_categories, package_types, warehouses, trucks.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_productions');
    }
};
