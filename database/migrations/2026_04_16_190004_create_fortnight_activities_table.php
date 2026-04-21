<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fortnight_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workforce_id');
            $table->date('date');
            $table->enum('fortnight_period', ['Q1', 'Q2'])->comments("Q1:date between 1-15, Q2:date between 16-end_of_month"); // e.g. "2026-04-1" or "2026-04-2"
            $table->foreignId('section_id')->nullable()->comments('if section_id is assigned not null must be corporal_id null');
            $table->foreignId('corporal_id')->nullable()->comments('if corporal_id is assigned not null must be section_id null');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->timestamps();

            $table->index(['workforce_id', 'fortnight_period']);
            $table->index(['workforce_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortnight_activities');
    }
};
