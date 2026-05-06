<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fisheries are ports/harbors where fish is unloaded from vessels.
        Schema::create('fisheries', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique()->comment('Primary fishery name');
            $table->string("name_fr")->nullable()->comment('French name');
            $table->string("name_en")->nullable()->comment('English name');
            $table->string("name_es")->nullable()->comment('Spanish name');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Fishing ports where fish is landed. Referenced by charges, productions, shippings.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fisheries');
    }
};
