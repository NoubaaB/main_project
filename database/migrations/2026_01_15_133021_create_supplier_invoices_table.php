<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supplier invoices group one or more charges and/or productions into a single billable document.
        // Each invoice tracks the date, tracking numbers, and aggregated totals.
        // Optionally linked to a single supplier via supplier_id for direct invoices.
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('Date the invoice was issued');
            $table->json('tracking_numbers')->nullable()->comment('Array of invoice tracking/reference numbers');
            $table->decimal('total_price', 15, 2)->default(0.00)->comment('Sum of all charge and production prices in this invoice');
            $table->decimal('total_weight', 15, 2)->default(0.00)->comment('Sum of all charge and production weights in this invoice');
            $table->integer('print_counter')->default(0)->comment('Number of times this invoice has been printed');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()->comment('Optional direct supplier FK for single-supplier invoices');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Supplier invoices containing grouped charges and/or productions. Has many charges, productions, payments, and suppliers (via pivot). , and have relations to Supplier Settlement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
