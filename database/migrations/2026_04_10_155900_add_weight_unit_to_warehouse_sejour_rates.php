<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_sejour_rates', function (Blueprint $table) {
            $table->decimal('weight_unit', 12, 2)->default(1000.00)
                  ->after('service_unit_price')
                  ->comment('Weight unit in KG used to calculate T (e.g. 1000 means per tonne)');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_sejour_rates', function (Blueprint $table) {
            $table->dropColumn('weight_unit');
        });
    }
};
