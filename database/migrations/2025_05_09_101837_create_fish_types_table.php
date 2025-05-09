<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fish_types', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();
            $table->string("scientific_name")->unique();
            $table->string("image")->nullable();
            $table->string("description")->nullable();

            $table->foreignId('fish_familly_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fish_types');
    }
};
