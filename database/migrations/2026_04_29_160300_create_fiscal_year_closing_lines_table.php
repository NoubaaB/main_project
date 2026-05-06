<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-supplier breakdown within a fiscal year closing.
        // Each line stores the invoice total, payment total, and outstanding balance for one supplier.
        Schema::create('fiscal_year_closing_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_closing_id')->constrained('fiscal_year_closings')->cascadeOnDelete()->comment('FK to fiscal_year_closings parent record');
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete()->comment('FK to suppliers; the supplier this line belongs to');
            $table->decimal('invoice_total', 15, 2)->default(0.00)->comment('Total invoices for this supplier in the fiscal year');
            $table->decimal('payment_total', 15, 2)->default(0.00)->comment('Total payments for this supplier in the fiscal year');
            $table->decimal('balance', 15, 2)->default(0.00)->comment('Outstanding balance (invoices - payments) carried forward');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['fiscal_year_closing_id', 'supplier_id'], 'fyc_lines_closing_supplier_unique');
            $table->comment('Per-supplier breakdown within a fiscal year closing. Each line captures one supplier balance.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_year_closing_lines');
    }
};
