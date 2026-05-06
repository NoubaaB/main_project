<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stock transfer records between warehouses.
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('Transfer date');
            $table->decimal('total_weight', 14, 2)->default(0)->comment('Total weight transferred in KG');
            $table->json('transfer_tracking_number')->nullable()->comment('JSON array of transfer tracking numbers');
            $table->text('description')->nullable()->comment('Transfer notes');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Stock transfers between warehouses. BelongsToMany trucks (warehouse_transfer_truck). Has many detail_warehouse_transfers.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfers');
    }
};
