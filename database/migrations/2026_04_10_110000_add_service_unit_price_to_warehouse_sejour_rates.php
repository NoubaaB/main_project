<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_sejour_rates', function (Blueprint $table) {
            $table->decimal('service_unit_price', 10, 4)->default(0.0000)->after('daily_rate_per_kg')
                  ->comment('Service/operation fee per KG for this date range');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_sejour_rates', function (Blueprint $table) {
            $table->dropColumn('service_unit_price');
        });
    }
};
