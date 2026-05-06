<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guano types are fish by-products (emulsion, meal, oil) used as polymorphic stockable items.
        Schema::create('guano_types', function (Blueprint $table) {
            $table->id();
            $table->string("name_fr")->unique()->comment('French name of the guano/by-product');
            $table->string("name_en")->unique()->comment('English name');
            $table->string("name_es")->unique()->comment('Spanish name');
            $table->string("name_in_invoice")->unique()->comment('Name displayed on printed invoices');
            $table->string("scientific_name")->nullable()->comment('Scientific name');
            $table->string("image")->nullable()->comment('Path to image');
            $table->string("description")->nullable()->comment('Description');
            $table->string("process")->nullable()->comment('Processing method');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Fish by-products/emulsion types. Polymorphic stockable in detail_charges, detail_productions, detail_shippings, warehouse_balances.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guano_types');
    }
};
