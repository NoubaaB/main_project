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
        Schema::table('package_secondary_configs', function (Blueprint $table) {
            $table->json('components')->nullable()->after('scotch_price')->comment('Array of secondary packaging components [{key, name, unit, qte, price}]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_secondary_configs', function (Blueprint $table) {
            $table->dropColumn('components');
        });
    }
};
