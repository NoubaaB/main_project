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
        Schema::create('type_charges', function (Blueprint $table) {
            $table->id();
            $table->string("description")->nullable();
            $table->string("name_fr")->nullable();
            $table->string("name_en")->nullable();
            $table->string("name_es")->nullable();
            $table->boolean("allow_charge_details")->default(false);
            $table->boolean("allow_production_details")->default(false);
            $table->boolean("add_wieght")->default(true);
            $table->integer("list_type_details",)->default(0);
            $table->string("process")->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_charges');
    }
};
