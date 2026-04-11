<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_sejour_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('daily_rate_per_kg', 10, 4)->default(0.0000)
                  ->comment('Cost per KG per day for this date range');
            $table->timestamps();

            $table->index(['warehouse_id', 'start_date', 'end_date']);
            $table->comment('Variable séjour rates per warehouse per date range');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_sejour_rates');
    }
};
