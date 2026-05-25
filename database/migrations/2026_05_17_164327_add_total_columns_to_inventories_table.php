<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            if (!Schema::hasColumn('inventories', 'total_weight')) {
                $table->decimal('total_weight', 14, 2)->default(0)->after('description');
            }
            if (!Schema::hasColumn('inventories', 'total_price')) {
                $table->decimal('total_price', 14, 2)->default(0)->after('total_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['total_weight', 'total_price']);
        });
    }
};
