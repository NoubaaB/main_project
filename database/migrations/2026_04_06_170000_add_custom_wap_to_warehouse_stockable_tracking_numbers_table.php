<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_stockable_tracking_numbers', function (Blueprint $table) {
            $table->float('custom_wap')->nullable()
                ->comment('Manual override for Weighted Average Price');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_stockable_tracking_numbers', function (Blueprint $table) {
            $table->dropColumn('custom_wap');
        });
    }
};
