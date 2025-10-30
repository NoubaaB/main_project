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
        Schema::create('charge_ships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ship_id')->index()->nullable();
            $table->foreignId('charge_id')->index()->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This charge_ships table describe the many to many relationship between charges and ships tables' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charge_ships');
    }
};
