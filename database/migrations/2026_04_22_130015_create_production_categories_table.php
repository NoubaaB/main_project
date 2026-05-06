<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Production categories define types of processing with configurable field visibility.
        Schema::create('production_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Category name (internal)');
            $table->string('name_fr')->nullable()->comment('French name');
            $table->string('name_en')->nullable()->comment('English name');
            $table->string('name_es')->nullable()->comment('Spanish name');
            $table->text('description')->nullable()->comment('Description');
            $table->boolean('enable_reception_tracking_number')->default(false)->comment('Show reception tracking number');
            $table->boolean('enable_tracking_onp_numbers')->default(false)->comment('Show ONP tracking numbers');
            $table->boolean('enable_production_tracking_number')->default(false)->comment('Show production tracking number');
            $table->boolean('enable_production_type')->default(false)->comment('Show production type (intern/extern)');
            $table->boolean('enable_fishery_id')->default(false)->comment('Show fishery selection');
            $table->boolean('enable_ship_id')->default(false)->comment('Show ship selection');
            $table->boolean('enable_origin_id')->default(false)->comment('Show origin selection');
            $table->boolean('enable_truck_id')->default(false)->comment('Show truck selection');
            $table->boolean('enable_suppliers')->default(false)->comment('If true, productions can be linked to suppliers');
            $table->boolean('enable_clients')->default(false)->comment('If true, productions can be linked to clients');
            $table->boolean('enable_invoices')->default(false)->comment('If true, productions can be included in supplier invoices');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Types of production. Has many productions, detail_production_categories.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_categories');
    }
};
