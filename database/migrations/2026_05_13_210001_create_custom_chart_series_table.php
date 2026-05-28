<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_chart_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_chart_id')->constrained('custom_charts')->cascadeOnDelete();
            $table->string('name');
            $table->string('series_type')->default('column');            // line, column, area (for mixed charts)
            $table->string('color')->nullable();                         // Hex color
            $table->string('measure_unit')->default('Kg')->comment('Display unit: Kg, DH, DH/KG, %');
            $table->boolean('is_visible')->default(true)->comment('Whether this series is displayed in chart/statistic output');
            $table->string('data_source');                               // charges | productions | shippings | warehouses | fortnight_activities
            $table->string('aggregate_field')->default('total_weight');   // total_weight, total_price, count, etc.
            $table->string('aggregate_function')->default('sum');         // sum | avg | count | min | max
            $table->string('group_by')->default('month');                // day | month | year
            $table->json('filters')->nullable();                         // JSON filters
            $table->boolean('is_formula')->default(false);
            $table->string('formula_expression')->nullable();            // e.g. "(S1 / S2) * 100"
            $table->json('formula_series_refs')->nullable();             // References to other series indexes
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->comment('Series definitions for custom charts. Each series maps to a data source with filters and aggregation.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_chart_series');
    }
};
