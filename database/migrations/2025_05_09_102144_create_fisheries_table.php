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
        Schema::create('fisheries', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();
            $table->string("name_fr")->nullable();
            $table->string("name_en")->nullable();
            $table->string("name_es")->nullable();
            $table->string("process")->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This fisheries table has relationship with charges table as has many of charge records');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fisheries');
    }
};
