<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_control_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_control_id')->constrained('quality_controls')->onDelete('cascade');
            $table->string('check_name')->comment('Name of the check (e.g. Temperature, Appearance)');
            $table->enum('result', ['pass', 'fail', 'n_a'])->default('n_a')->comment('Check result');
            $table->string('value')->nullable()->comment('Measured value');
            $table->text('notes')->nullable()->comment('Notes for this specific check');
            $table->timestamps();

            $table->comment('Individual check items within a quality control inspection');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_control_items');
    }
};
