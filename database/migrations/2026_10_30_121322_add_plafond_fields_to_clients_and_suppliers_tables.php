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
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('enable_plafond')->default(false)->after('active_status')->comment('Whether credit plafond limit is enabled');
            $table->decimal('plafond_amount', 15, 2)->nullable()->after('enable_plafond')->comment('Max credit limit / plafond amount in MAD');
            $table->boolean('block_on_plafond')->default(false)->after('plafond_amount')->comment('Whether to block shipping creation when plafond is reached');
            $table->string('plafond_mode', 20)->default('monthly')->after('block_on_plafond')->comment('Plafond calculation mode: monthly or yearly');
            $table->string('plafond_blocking_scope', 30)->default('past_unpaid')->after('plafond_mode')->comment('Scope: past_unpaid or current_month_only');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('enable_plafond')->default(false)->after('active_status')->comment('Whether supplier debt plafond limit is enabled');
            $table->decimal('plafond_amount', 15, 2)->nullable()->after('enable_plafond')->comment('Max debt limit / plafond amount with supplier in MAD');
            $table->boolean('block_on_plafond')->default(false)->after('plafond_amount')->comment('Whether to block charge and production creation when plafond is reached');
            $table->string('plafond_mode', 20)->default('monthly')->after('block_on_plafond')->comment('Plafond calculation mode: monthly or yearly');
            $table->string('plafond_blocking_scope', 30)->default('past_unpaid')->after('plafond_mode')->comment('Scope: past_unpaid or current_month_only');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'enable_plafond',
                'plafond_amount',
                'block_on_plafond',
                'plafond_mode',
                'plafond_blocking_scope'
            ]);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'enable_plafond',
                'plafond_amount',
                'block_on_plafond',
                'plafond_mode',
                'plafond_blocking_scope'
            ]);
        });
    }
};
