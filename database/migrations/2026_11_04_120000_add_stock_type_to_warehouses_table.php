<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'stock_type')) {
                // 'company' = Entrepôt Société (Stock Propre)
                // 'third_party' = Entrepôt Tiers / Sous-Traitance (Marchandises d'autres sociétés)
                $table->string('stock_type', 50)->default('company')->after('warehouse_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (Schema::hasColumn('warehouses', 'stock_type')) {
                $table->dropColumn('stock_type');
            }
        });
    }
};

