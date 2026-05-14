<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_chart_series', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true)->after('measure_unit')
                  ->comment('Whether this series is displayed in chart/statistic output');
        });
    }

    public function down(): void
    {
        Schema::table('custom_chart_series', function (Blueprint $table) {
            $table->dropColumn('is_visible');
        });
    }
};
