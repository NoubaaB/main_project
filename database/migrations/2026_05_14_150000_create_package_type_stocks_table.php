<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tracks carton (package type) stock quantities per warehouse.
        Schema::create('package_type_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete()
                ->comment('FK to the carton source warehouse');
            $table->foreignId('package_type_id')->constrained('package_types')->cascadeOnDelete()
                ->comment('FK to the package type being tracked');
            $table->decimal('quantity', 12, 2)->default(0)
                ->comment('Current stock count of this package type');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['warehouse_id', 'package_type_id']);
            $table->comment('Tracks carton (package type) stock quantities on the carton source warehouse.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_type_stocks');
    }
};
