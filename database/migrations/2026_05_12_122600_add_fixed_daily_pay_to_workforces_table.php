<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workforces', function (Blueprint $table) {
            $table->boolean('fixed_daily_pay')->default(false)->after('salary_statement');
        });
    }

    public function down(): void
    {
        Schema::table('workforces', function (Blueprint $table) {
            $table->dropColumn('fixed_daily_pay');
        });
    }
};
