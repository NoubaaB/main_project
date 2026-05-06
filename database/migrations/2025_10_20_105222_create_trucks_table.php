<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Trucks transport fish between locations.
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string("registration_plate")->nullable()->comment('Vehicle registration/license plate number');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Transport vehicles. BelongsToMany charges (charge_truck), shippings (shipping_truck), warehouse_transfers (warehouse_transfer_truck).');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
