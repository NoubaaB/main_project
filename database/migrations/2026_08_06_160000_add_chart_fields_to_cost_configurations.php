<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cost_configurations', function (Blueprint $table) {
            $table->boolean('show_in_chart')->default(false)->after('enabled')
                  ->comment('Whether to display this cost as a line in Jupiter Dashboard chart');
            $table->string('chart_color', 9)->nullable()->after('show_in_chart')
                  ->comment('Hex color for the chart line e.g. #FF5722');
        });
    }

    public function down(): void
    {
        Schema::table('cost_configurations', function (Blueprint $table) {
            $table->dropColumn(['show_in_chart', 'chart_color']);
        });
    }
};
