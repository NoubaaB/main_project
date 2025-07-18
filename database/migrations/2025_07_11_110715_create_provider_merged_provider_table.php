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
        Schema::create('provider_merged_provider', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merged_provider_id');
            $table->unsignedBigInteger('provider_id');
            
            $table->foreign('merged_provider_id')->references('id')->on('merged_providers')->onDelete('cascade');
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
        Schema::dropIfExists('provider_merged_provider');
    }
};
