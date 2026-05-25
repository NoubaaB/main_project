<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update existing NULL rows to 'neutre'
        DB::table('detail_charge_categories')->whereNull('package_stock_operation')->update(['package_stock_operation' => 'neutre']);
        DB::table('detail_production_categories')->whereNull('package_stock_operation')->update(['package_stock_operation' => 'neutre']);
        DB::table('detail_shipping_categories')->whereNull('package_stock_operation')->update(['package_stock_operation' => 'neutre']);

        // Set column default to 'neutre'
        Schema::table('detail_charge_categories', function (Blueprint $table) {
            $table->string('package_stock_operation')->nullable()->default('neutre')->change();
        });
        Schema::table('detail_production_categories', function (Blueprint $table) {
            $table->string('package_stock_operation')->nullable()->default('neutre')->change();
        });
        Schema::table('detail_shipping_categories', function (Blueprint $table) {
            $table->string('package_stock_operation')->nullable()->default('neutre')->change();
        });
    }

    public function down(): void
    {
        Schema::table('detail_charge_categories', function (Blueprint $table) {
            $table->string('package_stock_operation')->nullable()->default(null)->change();
        });
        Schema::table('detail_production_categories', function (Blueprint $table) {
            $table->string('package_stock_operation')->nullable()->default(null)->change();
        });
        Schema::table('detail_shipping_categories', function (Blueprint $table) {
            $table->string('package_stock_operation')->nullable()->default(null)->change();
        });
    }
};
