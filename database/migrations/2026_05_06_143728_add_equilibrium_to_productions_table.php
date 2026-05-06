<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->decimal('fresh_reception_weight', 12, 2)->default(0)->after('total_weight');
            $table->decimal('frozen_production_weight', 12, 2)->default(0)->after('fresh_reception_weight');
            $table->decimal('guano_production_weight', 12, 2)->default(0)->after('frozen_production_weight');
            $table->decimal('equilibrium', 12, 2)->default(0)->after('guano_production_weight');
            $table->string('equilibrium_status')->default('balanced')->after('equilibrium'); // surplus, shortage, balanced
        });
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn([
                'fresh_reception_weight',
                'frozen_production_weight',
                'guano_production_weight',
                'equilibrium',
                'equilibrium_status',
            ]);
        });
    }
};
