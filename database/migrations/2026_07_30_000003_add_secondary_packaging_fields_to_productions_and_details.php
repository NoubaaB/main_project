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
        Schema::table('detail_productions', function (Blueprint $table) {
            $table->decimal('secondary_packaging_cost', 12, 2)->default(0.00)->after('total_price')->comment('Secondary packaging cost calculated for this detail production line');
            $table->json('secondary_packaging_details')->nullable()->after('secondary_packaging_cost')->comment('JSON breakdown of secondary packaging quantities and unit costs');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->decimal('total_secondary_packaging_cost', 12, 2)->default(0.00)->after('total_price')->comment('Total cost of secondary packaging across all detail productions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_productions', function (Blueprint $table) {
            $table->dropColumn(['secondary_packaging_cost', 'secondary_packaging_details']);
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn('total_secondary_packaging_cost');
        });
    }
};
