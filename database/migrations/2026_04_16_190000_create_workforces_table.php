<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workforces', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('matricule')->unique();
            $table->string('nationality')->nullable();
            $table->enum('sex', ['male', 'female'])->default('male');
            $table->string('cni')->nullable();
            $table->boolean('is_migrant')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workforces');
    }
};
