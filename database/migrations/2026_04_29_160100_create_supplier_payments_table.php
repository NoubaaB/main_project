<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supplier payments track individual payments made to suppliers against their invoices.
        // Each payment records the type (cash, check, transfer, etc.), amount, and optional banking details.
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('Date the payment was made');
            $table->foreignId('supplier_invoice_id')->nullable()->constrained('supplier_invoices')->nullOnDelete()->comment('FK to supplier_invoices; null if payment is not linked to a specific invoice');
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete()->comment('FK to suppliers; the supplier receiving this payment');
            $table->enum('payment_type', ['cash', 'check', 'bank_transfer', 'bill_of_exchange', 'offset'])->default('cash')->comment('Method of payment');
            $table->decimal('amount', 15, 2)->default(0.00)->comment('Payment amount in local currency');
            $table->string('reference')->nullable()->comment('Payment reference number (check number, transfer ID, etc.)');
            $table->string('bank_name')->nullable()->comment('Bank name for check/transfer payments');
            $table->date('due_date')->nullable()->comment('Due date for deferred payments (checks, bills of exchange)');
            $table->text('notes')->nullable()->comment('Additional notes or remarks about the payment');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['supplier_id', 'date']);
            $table->index('date');
            $table->comment('Individual payment records against supplier invoices. Supports cash, check, bank transfer, bill of exchange, and offset.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
