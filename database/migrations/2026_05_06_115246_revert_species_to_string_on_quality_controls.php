<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // On fresh install, species is already a string column — nothing to revert
        if (DB::table('quality_controls')->count() === 0) {
            return;
        }

        Schema::table('quality_controls', function (Blueprint $table) {
            $table->string('species', 255)->nullable()->change();
        });

        $rows = DB::table('quality_controls')->whereNotNull('species')->where('species', '!=', '')->get(['id', 'species']);
        foreach ($rows as $row) {
            $decoded = json_decode($row->species, true);
            $val = is_array($decoded) ? ($decoded[0] ?? null) : $row->species;
            DB::table('quality_controls')->where('id', $row->id)->update(['species' => $val]);
        }
    }

    public function down(): void
    {
        Schema::table('quality_controls', function (Blueprint $table) {
            $table->json('species')->nullable()->change();
        });
    }
};
