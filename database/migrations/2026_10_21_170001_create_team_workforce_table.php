<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_workforce', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('workforce_id')->constrained('workforces')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'workforce_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_workforce');
    }
};

