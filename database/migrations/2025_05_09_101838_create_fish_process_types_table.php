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
        Schema::create('fish_process_types', function (Blueprint $table) {
            $table->id();

            $table->string("name")->unique();
            $table->string("description")->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This fish_process_types table has relationship with fish_types table as has many of fish_type records' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fish_process_types');
    }
};
