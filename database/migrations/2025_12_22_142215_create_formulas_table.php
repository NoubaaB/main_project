<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Custom formulas for pricing/calculation with configurable expressions and chart visualization.
        Schema::create('formulas', function (Blueprint $table) {
            $table->id();
            $table->string("name_fr")->unique()->comment('French formula name');
            $table->string("name_en")->unique()->comment('English formula name');
            $table->string("name_es")->unique()->comment('Spanish formula name');
            $table->text('description')->nullable()->comment('Description of what the formula calculates');
            $table->json('expressions')->comment('JSON: formula expression, chart config (color, type), and variable definitions referencing charge/sell categories');
            $table->boolean('enabled')->default(true)->comment('Whether the formula is active');
            $table->enum('type', ['charge', 'sell'])->nullable()->comment('Formula applies to charge or sell data');

            $table->foreignId('created_by')->nullable()->constrained('users')->comment('FK to users - who created this formula');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Custom pricing/calculation formulas. BelongsTo users (created_by).');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formulas');
    }
};
