<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Client invoices group multiple shippings/productions into a single billing document.
        Schema::create('client_invoices', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable()->comment('Invoice issue date');
            $table->enum('type_invoice', ['local', 'export'])->nullable()->comment('Invoice type: local or export');
            $table->json('tracking_numbers')->nullable()->comment('JSON array of invoice tracking numbers');
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete()->comment('FK to clients - the single client for this invoice');
            $table->json('client_ids')->nullable()->comment('Legacy JSON array of client IDs (deprecated, use client_id FK)');
            $table->decimal('total_price', 15, 2)->default(0.00)->comment('Sum of all shipping prices in MAD');
            $table->decimal('total_weight', 15, 2)->default(0.00)->comment('Sum of all shipping weights in KG');
            $table->integer('print_counter')->default(0)->comment('Number of times this invoice has been printed');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('Client invoices grouping shippings and productions. Has many shippings (FK invoice_id), productions (FK client_invoice_id).');
        });

        // Add FK from shippings.invoice_id -> client_invoices after table is created
        Schema::table('shippings', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('client_invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shippings', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });
        Schema::dropIfExists('client_invoices');
    }
};
