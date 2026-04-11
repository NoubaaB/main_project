<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fish_types', function (Blueprint $table) {
            $table->string('productivity_color')->nullable()->after('fish_process_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('fish_types', function (Blueprint $table) {
            $table->dropColumn('productivity_color');
        });
    }
};
