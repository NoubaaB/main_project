<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if table was already created with production_id (fresh install)
        if (Schema::hasColumn('quality_controls', 'production_id')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('quality_control_items')->truncate();
        DB::table('quality_controls')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        if (Schema::hasColumn('quality_controls', 'charge_id')) {
            Schema::table('quality_controls', function (Blueprint $table) {
                try { $table->dropForeign(['charge_id']); } catch (\Exception $e) {}
            });
            Schema::table('quality_controls', function (Blueprint $table) {
                $table->dropColumn('charge_id');
            });
        }

        Schema::table('quality_controls', function (Blueprint $table) {
            $table->foreignId('production_id')->after('id')->constrained('productions')->cascadeOnDelete();
        });

        if (Schema::hasColumn('charge_categories', 'enable_quality_control')) {
            Schema::table('charge_categories', function (Blueprint $table) {
                $table->dropColumn('enable_quality_control');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('quality_controls', 'production_id')) {
            Schema::table('quality_controls', function (Blueprint $table) {
                $table->dropForeign(['production_id']);
                $table->dropColumn('production_id');
            });
        }

        if (!Schema::hasColumn('quality_controls', 'charge_id')) {
            Schema::table('quality_controls', function (Blueprint $table) {
                $table->unsignedBigInteger('charge_id')->after('id');
            });
        }

        if (!Schema::hasColumn('charge_categories', 'enable_quality_control')) {
            Schema::table('charge_categories', function (Blueprint $table) {
                $table->boolean('enable_quality_control')->default(false)->after('enable_invoices');
            });
        }
    }
};
