<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_transfer_truck', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_transfer_id')->constrained('warehouse_transfers')->cascadeOnDelete()->comment('FK to warehouse_transfers');
            $table->foreignId('truck_id')->constrained('trucks')->cascadeOnDelete()->comment('FK to trucks');
            $table->comment('Pivot: many-to-many warehouse_transfers <-> trucks.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfer_truck');
    }
};
