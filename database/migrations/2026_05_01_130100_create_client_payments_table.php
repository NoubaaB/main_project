<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_payments', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('Date of the payment');
            $table->foreignId('client_invoice_id')->nullable()->constrained('client_invoices')->nullOnDelete()->comment('FK to client_invoices');
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete()->comment('FK to clients; the client receiving this payment');
            $table->string('payment_type')->comment('cash, check, bank_transfer, bill_of_exchange, offset');
            $table->decimal('amount', 14, 2)->comment('Payment amount');
            $table->string('reference')->nullable()->comment('Payment reference / check number');
            $table->string('bank_name')->nullable()->comment('Bank name for check/transfer');
            $table->date('due_date')->nullable()->comment('Due date for check/bill of exchange');
            $table->text('notes')->nullable()->comment('Additional notes');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'date']);
            $table->comment('Client payment records. BelongsTo clients, client_invoices.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_payments');
    }
};
