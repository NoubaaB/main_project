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
        Schema::create('merged_providers', function (Blueprint $table) {
            $table->id();
            $table->string("name", 50)->unique();
            $table->string("ice", 100)->unique();
            $table->string("country", 100)->nullable();
            $table->string("city", 100)->nullable();
            $table->string("address", 250)->nullable();
            $table->string("tel", 25)->nullable();
            // $table->string("logo",25)->nullable(); //for production, you can use a URL or a file path
            $table->string("logo")->nullable(); // for testing purposes, you can use a URL or a file path
            $table->boolean("state")->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merged_providers');
    }
};
