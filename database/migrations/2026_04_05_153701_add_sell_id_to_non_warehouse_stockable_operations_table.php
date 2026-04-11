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
        Schema::table('non_warehouse_stockable_operations', function (Blueprint $table) {
            $table->foreignId('sell_id')->nullable()->index()->after('charge_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('non_warehouse_stockable_operations', function (Blueprint $table) {
            $table->dropColumn('sell_id');
        });
    }
};
