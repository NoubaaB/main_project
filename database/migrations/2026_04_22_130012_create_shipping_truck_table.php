<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_truck', function (Blueprint $table) {
            $table->foreignId('shipping_id')->constrained()->cascadeOnDelete()->comment('FK to shippings');
            $table->foreignId('truck_id')->constrained()->cascadeOnDelete()->comment('FK to trucks');
            $table->primary(['shipping_id', 'truck_id']);
            $table->comment('Pivot: many-to-many shippings <-> trucks.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_truck');
    }
};
