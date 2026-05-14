<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_chart_series', function (Blueprint $table) {
            $table->string('measure_unit')->default('Kg')->after('color')
                  ->comment('Display unit: Kg, DH, DH/KG, %');
        });
    }

    public function down(): void
    {
        Schema::table('custom_chart_series', function (Blueprint $table) {
            $table->dropColumn('measure_unit');
        });
    }
};
