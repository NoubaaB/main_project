<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Shippings represent fish sales/dispatches (outgoing goods) to clients.
        Schema::create('shippings', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('Date the sale was recorded');
            $table->decimal('total_price', 14, 2)->default(0)->comment('Sum of all line item prices in MAD');
            $table->decimal('total_weight', 14, 2)->default(0)->comment('Sum of all line item weights in KG');
            $table->foreignId('shipping_category_id')->constrained('shipping_categories')->cascadeOnDelete()->comment('FK to shipping_categories');
            $table->json('shipping_tracking_number')->nullable()->comment('JSON array of shipping tracking numbers');
            $table->json('tracking_onp_numbers')->nullable()->comment('JSON array of ONP tracking numbers');
            $table->text('description')->nullable()->comment('Additional notes');
            $table->foreignId('fishery_id')->nullable()->constrained('fisheries')->nullOnDelete()->comment('FK to fisheries');
            $table->foreignId('ship_id')->nullable()->constrained('ships')->nullOnDelete()->comment('FK to ships');
            $table->foreignId('origin_id')->nullable()->constrained('origins')->nullOnDelete()->comment('FK to origins');
            $table->string('direction')->nullable()->comment('Sale direction: local or export');
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete()->comment('FK to clients - the single client for this shipping');
            $table->boolean('allow_invoice')->default(true)->comment('Whether this shipping can be included in client invoices');
            $table->unsignedBigInteger('invoice_id')->nullable()->comment('FK to client_invoices - null if not yet invoiced');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Fish sale transactions. BelongsTo shipping_categories, fisheries, ships, origins, clients, client_invoices. BelongsToMany trucks (shipping_truck). Has many detail_shippings.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shippings');
    }
};
