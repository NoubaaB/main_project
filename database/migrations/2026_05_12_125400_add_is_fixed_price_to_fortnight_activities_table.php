<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fortnight_activities', function (Blueprint $table) {
            $table->boolean('is_fixed_price')->default(false)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('fortnight_activities', function (Blueprint $table) {
            $table->dropColumn('is_fixed_price');
        });
    }
};
