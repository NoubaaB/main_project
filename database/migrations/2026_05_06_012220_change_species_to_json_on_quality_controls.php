<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // On fresh install, species is already a string column — nothing to convert
        if (DB::table('quality_controls')->count() === 0) {
            return;
        }

        // Convert existing string values to JSON arrays
        DB::table('quality_controls')->whereNotNull('species')->where('species', '!=', '')->update([
            'species' => DB::raw("JSON_ARRAY(species)")
        ]);
        DB::table('quality_controls')->where(function ($q) {
            $q->whereNull('species')->orWhere('species', '');
        })->update(['species' => null]);

        Schema::table('quality_controls', function (Blueprint $table) {
            $table->json('species')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('quality_controls', function (Blueprint $table) {
            $table->string('species')->nullable()->change();
        });
    }
};
