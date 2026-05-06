<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daily storage rates per warehouse, supporting time-range pricing.
        Schema::create('sejour_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete()->comment('FK to warehouses');
            $table->date('start_date')->comment('Rate effective start date');
            $table->date('end_date')->nullable()->comment('Rate effective end date (null = ongoing)');
            $table->decimal('daily_rate_per_kg', 10, 4)->default(0)->comment('Daily storage fee per KG per weight_unit in MAD');
            $table->decimal('service_unit_price', 10, 4)->default(0)->comment('Service fee per unit operation in MAD');
            $table->integer('weight_unit')->default(1000)->comment('Weight unit in KG for rate calculation (e.g., 1000 = per ton)');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'start_date', 'end_date']);
            $table->comment('Daily storage rates per warehouse. BelongsTo warehouses. Calculates storage fees by weight and duration.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sejour_rates');
    }
};
