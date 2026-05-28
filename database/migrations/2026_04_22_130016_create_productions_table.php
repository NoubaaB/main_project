<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Productions represent fish processing/transformation records.
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('Date the production was recorded');
            $table->decimal('total_price', 14, 2)->default(0)->comment('Sum of all line item prices in MAD');
            $table->decimal('total_weight', 14, 2)->default(0)->comment('Sum of all line item weights in KG');
            $table->decimal('fresh_reception_weight', 12, 2)->default(0);
            $table->decimal('frozen_production_weight', 12, 2)->default(0);
            $table->decimal('guano_production_weight', 12, 2)->default(0);
            $table->decimal('equilibrium', 12, 2)->default(0);
            $table->string('equilibrium_status')->default('balanced');
            $table->foreignId('production_category_id')->constrained('production_categories')->cascadeOnDelete()->comment('FK to production_categories');
            $table->json('reception_tracking_number')->nullable()->comment('JSON array of reception tracking numbers');
            $table->text('description')->nullable()->comment('Additional notes');
            $table->json('tracking_onp_numbers')->nullable()->comment('JSON array of ONP tracking numbers');
            $table->json('production_tracking_number')->nullable()->comment('JSON array of production tracking numbers');
            $table->string('production_type')->nullable()->comment('Processing type: intern (in-house) or extern (outsourced)');
            $table->foreignId('fishery_id')->nullable()->constrained('fisheries')->nullOnDelete()->comment('FK to fisheries');
            $table->foreignId('ship_id')->nullable()->constrained('ships')->nullOnDelete()->comment('FK to ships');
            $table->foreignId('origin_id')->nullable()->constrained('origins')->nullOnDelete()->comment('FK to origins');
            $table->foreignId('charge_id')->nullable()->constrained('charges')->nullOnDelete()->comment('FK to charges - source charge being processed');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()->comment('FK to suppliers - the single supplier for this production');
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete()->comment('FK to clients - the single client for this production');
            $table->boolean('allow_invoice')->default(true)->comment('Whether this production can be included in invoices');
            $table->unsignedBigInteger('supplier_invoice_id')->nullable()->comment('FK to supplier_invoices - null if not yet invoiced');
            $table->unsignedBigInteger('client_invoice_id')->nullable()->comment('FK to client_invoices - null if not yet invoiced');
            $table->foreign('supplier_invoice_id')->references('id')->on('supplier_invoices')->nullOnDelete();
            $table->foreign('client_invoice_id')->references('id')->on('client_invoices')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Fish processing records. BelongsTo production_categories, fisheries, ships, origins, charges, suppliers, clients, supplier_invoices, client_invoices. Has many detail_productions.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
