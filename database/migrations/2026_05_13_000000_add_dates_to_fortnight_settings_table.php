<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fortnight_settings', function (Blueprint $table) {
            $table->date('date_start')->after('id')->default('2026-01-01');
            $table->date('date_end')->nullable()->after('date_start');
        });

        // Update existing rows to have a sensible date_start
        \DB::table('fortnight_settings')->whereNull('date_start')->orWhere('date_start', '0000-00-00')->update([
            'date_start' => '2026-01-01',
            'date_end' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('fortnight_settings', function (Blueprint $table) {
            $table->dropColumn(['date_start', 'date_end']);
        });
    }
};
