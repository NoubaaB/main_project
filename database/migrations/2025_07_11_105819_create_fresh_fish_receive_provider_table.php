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
        Schema::create('fresh_fish_receive_provider', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fresh_fish_receive_id');
            $table->unsignedBigInteger('provider_id');

            $table->foreign('fresh_fish_receive_id')->references('id')->on('fresh_fish_receives')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('providers')->onDelete('cascade');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fresh_fish_receive_provider');
    }
};
