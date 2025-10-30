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
        Schema::create('charge_categories', function (Blueprint $table) {
            $table->id();
            $table->string("description")->nullable();
            $table->string("name_fr")->nullable();
            $table->string("name_en")->nullable();
            $table->string("name_es")->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This type_charge table describe the category of charge and have relationship with charges table as has many of charge records');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charge_categories');
    }
};
