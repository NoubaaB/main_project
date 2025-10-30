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
        Schema::create('ships', function (Blueprint $table) {
            $table->id();

            $table->string("name")->nullable();
            $table->string("imo_number")->nullable()->comment("This column is ship unique Identifier its means International Maritime Organization (IMO) number");

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This table stores ships information including their names and IMO numbers.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ships');
    }
};
