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
        Schema::create('guano_types', function (Blueprint $table) {
            $table->id();
            $table->string("name_fr")->unique();
            $table->string("name_en")->unique();
            $table->string("name_es")->unique();
            $table->string("name_in_invoice")->unique();
            $table->string("scientific_name")->nullable();
            $table->string("image")->nullable();
            $table->string("description")->nullable();
            $table->string("process")->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This guano_types table describe fish emulsion and has belong to many of warehouse_stockable_tracking_number_operations records in table warehouse_stockable_tracking_number_operations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guano_types');
    }
};
