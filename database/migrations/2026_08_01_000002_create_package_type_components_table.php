<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('package_type_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_type_id')->constrained('package_types')->cascadeOnDelete()
                ->comment('Parent package type, e.g. Carton');
            $table->foreignId('target_package_type_id')->constrained('package_types')->cascadeOnDelete()
                ->comment('Target package type consumed in production/shipping, e.g. Package 24');
            $table->decimal('multiplier', 10, 4)->default(1.0000)
                ->comment('Quantity of parent package type consumed per box of target package type');
            $table->timestamps();

            $table->unique(['package_type_id', 'target_package_type_id'], 'pkg_comp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_type_components');
    }
};
