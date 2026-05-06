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
        // Package types represent the physical containers/packaging used for fish products.
        // Each package type has multilingual names (FR, EN, ES), a size, and optional descriptions.
        // Related to detail_charges, detail_productions, and detail_shippings as a lookup.
        Schema::create('package_types', function (Blueprint $table) {
            $table->id();
            $table->string("name_fr")->unique()->comment('French name of the package type');
            $table->string("name_en")->unique()->comment('English name of the package type');
            $table->string("name_es")->unique()->comment('Spanish name of the package type');
            $table->string("name_in_invoice")->nullable()->comment('Name displayed on printed invoices (optional override)');
            $table->integer("size")->comment('Capacity/size of the package in kg');
            $table->string("description")->nullable()->comment('Optional description or notes about the package type');
            $table->string("type_emballage")->nullable()->comment('Type of packaging material (e.g., carton, plastic, styrofoam)');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Package types (emballages) used as containers for fish products. Has many detail_charges, detail_productions, and detail_shippings.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_types');
    }
};
