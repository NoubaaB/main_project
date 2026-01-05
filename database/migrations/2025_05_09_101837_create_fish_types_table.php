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
        Schema::create('fish_types', function (Blueprint $table) {
            $table->id();
            $table->string("name_fr")->unique();
            $table->string("name_en")->unique();
            $table->string("name_es")->unique();
            $table->string("name_in_invoice")->unique();
            $table->string("scientific_name")->nullable();
            $table->string("image")->nullable();
            $table->string("description")->nullable();
            $table->string("process")->nullable();

            $table->foreignId('fish_family_id')->nullable()->index();
            $table->foreignId('fish_process_type_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
            
            $table->comment('This fish_types table has belong to one fish_family record in table fish_fammilies also has belong to one fish_process_type record in table fish_process_types , and have reltionship with charge_details table as has many of charge_detail records and production_details table as has many of production_detail records' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fish_types');
    }
};
