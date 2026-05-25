<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add is_carton_source flag to warehouses
        Schema::table('warehouses', function (Blueprint $table) {
            $table->boolean('is_carton_source')->default(false)->after('total_weight')
                ->comment('Only one warehouse can be the source of cartons (package types)');
        });

        // 2. Add package_stock_operation to detail_charge_categories
        Schema::table('detail_charge_categories', function (Blueprint $table) {
            $table->string('package_stock_operation')->nullable()->after('allow_nbr_box')
                ->comment('How nbr_box affects carton stock: stock_in or stock_out');
        });

        // 3. Add package_stock_operation to detail_production_categories
        Schema::table('detail_production_categories', function (Blueprint $table) {
            $table->string('package_stock_operation')->nullable()->after('allow_nbr_box')
                ->comment('How nbr_box affects carton stock: stock_in or stock_out');
        });

        // 4. Add package_stock_operation to detail_shipping_categories
        Schema::table('detail_shipping_categories', function (Blueprint $table) {
            $table->string('package_stock_operation')->nullable()->after('allow_nbr_box')
                ->comment('How nbr_box affects carton stock: stock_in or stock_out');
        });

        // 5. Create package_type_stocks table to track carton stock per package type
        Schema::create('package_type_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete()
                ->comment('FK to the carton source warehouse');
            $table->foreignId('package_type_id')->constrained('package_types')->cascadeOnDelete()
                ->comment('FK to the package type being tracked');
            $table->decimal('quantity', 12, 2)->default(0)
                ->comment('Current stock count of this package type');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['warehouse_id', 'package_type_id']);
            $table->comment('Tracks carton (package type) stock quantities on the carton source warehouse.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_type_stocks');

        Schema::table('detail_shipping_categories', function (Blueprint $table) {
            $table->dropColumn('package_stock_operation');
        });

        Schema::table('detail_production_categories', function (Blueprint $table) {
            $table->dropColumn('package_stock_operation');
        });

        Schema::table('detail_charge_categories', function (Blueprint $table) {
            $table->dropColumn('package_stock_operation');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn('is_carton_source');
        });
    }
};
