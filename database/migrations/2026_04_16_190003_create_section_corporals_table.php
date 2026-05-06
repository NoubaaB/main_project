<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot table linking sections to their assigned corporals.
        Schema::create('section_corporals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade')->comment('FK to sections');
            $table->foreignId('corporal_id')->constrained('corporals')->onDelete('cascade')->comment('FK to corporals');
            $table->timestamps();

            $table->unique(['section_id', 'corporal_id']);
            $table->comment('Pivot: many-to-many sections <-> corporals.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_corporals');
    }
};
