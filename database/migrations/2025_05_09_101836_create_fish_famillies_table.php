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
        Schema::create('fish_famillies', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();

            $table->timestamps();
            $table->softDeletes();
            $table->comment("This fish_famillies table store fish familly's names and have one to many relationship with users table");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fish_famillies');
    }
};
