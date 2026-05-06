<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charge_categories', function (Blueprint $table) {
            $table->boolean('enable_quality_control')->default(false)->after('enable_invoices')->comment('If true, charges of this category can have quality control inspections');
        });
    }

    public function down(): void
    {
        Schema::table('charge_categories', function (Blueprint $table) {
            $table->dropColumn('enable_quality_control');
        });
    }
};
