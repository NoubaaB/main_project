<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Suppliers provide fish and other goods. Central entity linked to charges, productions, invoices, and payments.
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique()->comment('Supplier company or individual name');
            $table->string("phone",20)->nullable()->comment('Contact phone number');
            $table->string("ice",50)->nullable()->comment('ICE tax identification number (Morocco)');
            $table->string("email",50)->nullable()->comment('Contact email address');
            $table->string("address",100)->nullable()->comment('Street address');
            $table->string("city",50)->nullable()->comment('City');
            $table->string("country",200)->nullable()->comment('Country');
            $table->string("postal_code",10)->nullable()->comment('Postal/ZIP code');
            $table->string("website",200)->nullable()->comment('Website URL');
            $table->string("description",200)->nullable()->comment('Additional notes about the supplier');
            $table->string("logo_image",200)->nullable()->comment('Path to supplier logo image');
            $table->boolean("active_status")->default(true)->comment('Whether the supplier is currently active');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Fish suppliers. BelongsToMany charges (charge_supplier), productions (production_supplier), supplier_invoices (supplier_invoice_supplier). Has many supplier_invoices, supplier_payments. MorphOne profit_and_loss_accounts.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
