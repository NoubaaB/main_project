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
        Schema::table('detail_production_categories', function (Blueprint $table) {
            $table->boolean('allow_secondary_packaging')->default(false)->after('include_in_invoice')->comment('If true, calculates secondary packaging cost for detail productions in this category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_production_categories', function (Blueprint $table) {
            $table->dropColumn('allow_secondary_packaging');
        });
    }
};
