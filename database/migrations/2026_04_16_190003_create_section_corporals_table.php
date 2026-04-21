<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_corporals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('corporal_id')->constrained('corporals')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['section_id', 'corporal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_corporals');
    }
};
