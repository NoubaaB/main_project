<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('corporals', function (Blueprint $table) {
            $table->decimal('daily_price', 10, 2)->default(0)->after('is_quinzaine')->comment('Default daily price for this corporal');
        });
    }

    public function down(): void
    {
        Schema::table('corporals', function (Blueprint $table) {
            $table->dropColumn('daily_price');
        });
    }
};
