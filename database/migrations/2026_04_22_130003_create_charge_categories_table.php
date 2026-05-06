<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Charge categories define types of purchases with configurable field visibility.
        Schema::create('charge_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Category name (default/internal)');
            $table->string('name_fr')->nullable()->comment('French category name');
            $table->string('name_en')->nullable()->comment('English category name');
            $table->string('name_es')->nullable()->comment('Spanish category name');
            $table->text('description')->nullable()->comment('Category description');
            $table->boolean('enable_reception_tracking_number')->default(false)->comment('Show reception tracking number field');
            $table->boolean('enable_tracking_onp_numbers')->default(false)->comment('Show ONP tracking numbers field');
            $table->boolean('enable_fishery_id')->default(false)->comment('Show fishery selection');
            $table->boolean('enable_ship_id')->default(false)->comment('Show ship selection');
            $table->boolean('enable_origin_id')->default(false)->comment('Show origin selection');
            $table->boolean('enable_truck_id')->default(false)->comment('Show truck selection');
            $table->boolean('enable_suppliers')->default(false)->comment('Show supplier selection');
            $table->boolean('is_production')->default(false)->comment('If true, this charge category represents production processing');
            $table->boolean('enable_invoices')->default(false)->comment('If true, charges of this category can be included in supplier invoices');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Types of purchases. is_production=true means production. Has many charges, detail_charge_categories.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_categories');
    }
};
