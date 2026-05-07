<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Monthly storage invoices per warehouse (Facture de Stockage).
        Schema::create('storage_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete()->comment('FK to warehouses');
            $table->string('invoice_number')->unique()->comment('Invoice number e.g. 02V003');
            $table->integer('year')->comment('Invoice year');
            $table->integer('month')->comment('Invoice month (1-12)');
            $table->decimal('total_stock_in_kg', 14, 2)->default(0)->comment('Total stock in weight for the month (KG)');
            $table->decimal('total_stock_out_kg', 14, 2)->default(0)->comment('Total stock out weight for the month (KG)');
            $table->decimal('manipulation_in_amount', 14, 2)->default(0)->comment('Manipulation fee for stock in (MAD)');
            $table->decimal('manipulation_out_amount', 14, 2)->default(0)->comment('Manipulation fee for stock out (MAD)');
            $table->decimal('sejour_amount', 14, 2)->default(0)->comment('Total séjour/storage fee (MAD)');
            $table->decimal('total_amount', 14, 2)->default(0)->comment('Total invoice amount (MAD)');
            $table->decimal('service_unit_price', 10, 4)->default(0)->comment('Snapshot: service fee per KG at generation time');
            $table->decimal('daily_rate_per_kg', 10, 4)->default(0)->comment('Snapshot: daily rate per KG at generation time');
            $table->integer('weight_unit')->default(1000)->comment('Snapshot: weight unit at generation time');
            $table->enum('status', ['draft', 'finalized'])->default('finalized')->comment('Invoice status');
            $table->integer('print_counter')->default(0)->comment('Number of times printed');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who generated');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['warehouse_id', 'year', 'month'], 'si_wh_year_month');
            $table->comment('Monthly storage invoices per warehouse. BelongsTo warehouses, users. Has many storage_invoice_lines.');
        });

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
        Schema::dropIfExists('storage_invoices');
    }
};
