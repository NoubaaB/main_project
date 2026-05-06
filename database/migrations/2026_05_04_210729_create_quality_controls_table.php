<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
            $table->string('lot_id')->nullable();
            $table->string('species')->nullable();
            $table->decimal('quantity_kg', 12, 2)->nullable();
            $table->date('catch_date')->nullable();
            $table->string('storage_type')->nullable();
            $table->string('inspection_type')->nullable();
            $table->string('inspector_name')->nullable()->comment('Name of the quality inspector');
            $table->date('inspection_date')->comment('Date of the inspection');
            $table->decimal('temperature', 8, 2)->nullable()->comment('Measured temperature (°C)');
            $table->string('skin_grade')->nullable();
            $table->string('eye_grade')->nullable();
            $table->string('gills_grade')->nullable();
            $table->string('abdomen_grade')->nullable();
            $table->string('backbone_grade')->nullable();
            $table->string('organs_grade')->nullable();
            $table->string('texture_grade')->nullable();
            $table->string('odor_grade')->nullable();
            $table->string('overall_grade')->nullable();
            $table->string('decision')->nullable();
            $table->text('notes')->nullable()->comment('General QC notes');
            $table->text('corrective_action')->nullable();
            $table->boolean('reinspection_required')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approval_status')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Quality control inspections for productions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_controls');
    }
};
