<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_charts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('chart_type')->default('bar');                // line, bar, area, pie, donut, radar, heatmap, mixed
            $table->json('chart_options')->nullable();                    // Full ApexCharts options JSON
            $table->string('date_range_type')->default('dynamic');       // dynamic | fixed_month | fixed_year | all
            $table->integer('fixed_month')->nullable();                  // 1-12
            $table->integer('fixed_year')->nullable();
            $table->date('dynamic_start')->nullable();
            $table->date('dynamic_end')->nullable();
            $table->string('display_type')->default('chart');            // chart | statistic
            $table->integer('display_order')->default(0);
            $table->integer('grid_cols')->default(6);                    // Vuetify grid cols (1-12)
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->comment('User-defined chart/statistic configurations for the main dashboard.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_charts');
    }
};
