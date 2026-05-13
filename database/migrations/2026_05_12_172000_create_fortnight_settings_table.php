<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fortnight_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('q1_max_salary', 10, 2)->default(0);
            $table->decimal('q2_max_salary', 10, 2)->default(0);
            $table->timestamps();
        });

        // Seed a default row
        \DB::table('fortnight_settings')->insert([
            'q1_max_salary' => 0,
            'q2_max_salary' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fortnight_settings');
    }
};
