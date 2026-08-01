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
        Schema::dropIfExists('cost_configurations');
        Schema::create('cost_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name_fr')->comment('French cost name');
            $table->string('name_en')->nullable()->comment('English cost name');
            $table->string('name_es')->nullable()->comment('Spanish cost name');
            $table->string('unit', 50)->default('DH/KG')->comment('Result unit e.g. KG, DH, DH/KG, %');
            $table->json('variables')->comment('JSON array of variable definitions (var_name, data_source, category_id, value_type)');
            $table->text('formula')->comment('Math formula expression e.g. ((c2+c3)/c1)*100');
            $table->text('description')->nullable()->comment('Description of cost calculation');
            $table->boolean('enabled')->default(true)->comment('Whether cost formula is active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('FK to users');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Cost configurations for dynamic production cost calculation formulas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_configurations');
    }
};
