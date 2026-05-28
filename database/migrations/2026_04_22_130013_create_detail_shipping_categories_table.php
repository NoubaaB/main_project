<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Field definitions for shipping line items (same structure as detail_charge_categories).
        Schema::create('detail_shipping_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_fr')->nullable()->comment('French name');
            $table->string('name_en')->nullable()->comment('English name');
            $table->string('name_es')->nullable()->comment('Spanish name');
            $table->text('description')->nullable()->comment('Description');
            $table->string('article_type')->default('text')->comment('Type of item: text, FishType, GuanoType, or PackageType');
            $table->enum('fish_type_filter', ['all', 'fresh', 'frozen'])->default('all')->comment('Filter fish types: all, fresh, or frozen');
            $table->boolean('allow_weight')->default(false)->comment('Show weight field');
            $table->boolean('allow_unit_price')->default(false)->comment('Show unit price field');
            $table->boolean('allow_price')->default(false)->comment('Show total price field');
            $table->boolean('allow_package_type')->default(false)->comment('Show package type selection');
            $table->boolean('allow_nbr_box')->default(false)->comment('Show number of boxes field');
            $table->string('package_stock_operation')->nullable()->default('neutre')->comment('How nbr_box affects carton stock: stock_in, stock_out, or neutre');
            $table->boolean('allow_warehouse')->default(false)->comment('Show warehouse selection');
            $table->string('warehouse_operation')->nullable()->comment('Default warehouse operation: stock_in or stock_out');
            $table->boolean('allow_detail_truck_id')->default(false)->comment('Show truck selection per line item');
            $table->boolean('include_in_invoice')->default(true)->comment('Whether this line type appears on invoices');
            $table->foreignId('shipping_category_id')->constrained('shipping_categories')->cascadeOnDelete()->comment('FK to shipping_categories');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Field definitions for shipping line items. BelongsTo shipping_categories. Has many detail_shippings.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_shipping_categories');
    }
};
