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
        Schema::table('sell_categories', function (Blueprint $table) {
            $table->boolean('enable_truck_id')->default(false)->after('enable_origin_id');
            $table->boolean('enable_client_process_id')->default(false)->after('enable_truck_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sell_categories', function (Blueprint $table) {
            $table->dropColumn(['enable_truck_id', 'enable_client_process_id']);
        });
    }
};
