<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot: links charges to trucks that transported them.
        Schema::create('charge_truck', function (Blueprint $table) {
            $table->foreignId('charge_id')->constrained()->cascadeOnDelete()->comment('FK to charges');
            $table->foreignId('truck_id')->constrained()->cascadeOnDelete()->comment('FK to trucks');
            $table->primary(['charge_id', 'truck_id']);

            $table->comment('Pivot: many-to-many charges <-> trucks.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_truck');
    }
};
