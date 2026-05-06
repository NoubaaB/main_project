<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fish families are top-level groupings of fish species (e.g., Clupeidae, Scombridae).
        Schema::create('fish_families', function (Blueprint $table) {
            $table->id();
            $table->string("name_fr")->unique()->comment('French name of the fish family');
            $table->string("name_en")->unique()->comment('English name of the fish family');
            $table->string("name_es")->unique()->comment('Spanish name of the fish family');

            $table->timestamps();
            $table->softDeletes();
            $table->comment('Fish family groupings (Clupeidae, Scombridae). Has many fish_types.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_families');
    }
};
