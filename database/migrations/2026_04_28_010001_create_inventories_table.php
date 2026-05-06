<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Physical inventory count records to verify actual stock vs system stock.
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('Date the inventory count was performed');
            $table->json('inventory_tracking_number')->nullable()->comment('JSON array of inventory tracking numbers');
            $table->text('description')->nullable()->comment('Notes about this inventory count');
            $table->decimal('total_weight', 14, 2)->default(0)->comment('Sum of all counted weights in KG');
            $table->decimal('total_price', 14, 2)->default(0)->comment('Sum of all counted values in MAD');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Physical inventory counts. Has many detail_inventories.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
