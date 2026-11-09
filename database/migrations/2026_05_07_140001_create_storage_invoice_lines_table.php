<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Individual line items on a storage invoice.
        Schema::create('storage_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_invoice_id')->constrained()->cascadeOnDelete()->comment('FK to storage_invoices');
            $table->string('label')->comment('Line type: manipulation_in, manipulation_out, sejour');
            $table->string('description')->nullable()->comment('Human readable description');
            $table->decimal('quantity_kg', 14, 2)->default(0)->comment('Weight in KG');
            $table->decimal('quantity_days', 10, 2)->default(0)->comment('Number of days (for séjour lines)');
            $table->decimal('unit_price', 10, 4)->default(0)->comment('Price per unit');
            $table->decimal('total_amount', 14, 2)->default(0)->comment('Line total (MAD)');
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->timestamps();

            $table->comment('Line items for storage_invoices. BelongsTo storage_invoices.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_invoice_lines');
    }
};

