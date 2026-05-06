<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Charges represent fish purchases (incoming goods) from suppliers.
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('Date the purchase was recorded');
            $table->decimal('total_price', 14, 2)->default(0)->comment('Sum of all line item prices in MAD');
            $table->decimal('total_weight', 14, 2)->default(0)->comment('Sum of all line item weights in KG');
            $table->foreignId('charge_category_id')->constrained('charge_categories')->cascadeOnDelete()->comment('FK to charge_categories - determines the type of purchase');
            $table->json('reception_tracking_number')->nullable()->comment('JSON array of reception tracking numbers from fishery');
            $table->json('tracking_onp_numbers')->nullable()->comment('JSON array of ONP tracking numbers');
            $table->foreignId('fishery_id')->nullable()->constrained('fisheries')->nullOnDelete()->comment('FK to fisheries - port where fish was landed');
            $table->foreignId('ship_id')->nullable()->constrained('ships')->nullOnDelete()->comment('FK to ships - vessel that caught the fish');
            $table->foreignId('origin_id')->nullable()->constrained('origins')->nullOnDelete()->comment('FK to origins - geographic origin of the fish');
            $table->text('description')->nullable()->comment('Additional notes about this charge');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()->comment('FK to suppliers - the single supplier for this charge');
            $table->boolean('allow_invoice')->default(true)->comment('Whether this charge can be included in supplier invoices');
            $table->unsignedBigInteger('invoice_id')->nullable()->comment('FK to supplier_invoices - null if not yet invoiced');
            $table->foreign('invoice_id')->references('id')->on('supplier_invoices')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Fish purchase transactions. BelongsTo charge_categories, fisheries, ships, origins, suppliers, supplier_invoices. BelongsToMany trucks (charge_truck). Has many detail_charges. HasOne production.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
