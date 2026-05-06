<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Shipping categories define types of sales with configurable field visibility.
        Schema::create('shipping_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Category name (internal)');
            $table->string('name_fr')->nullable()->comment('French name');
            $table->string('name_en')->nullable()->comment('English name');
            $table->string('name_es')->nullable()->comment('Spanish name');
            $table->text('description')->nullable()->comment('Description');
            $table->boolean('enable_shipping_tracking_number')->default(false)->comment('Show shipping tracking number field');
            $table->boolean('enable_tracking_onp_numbers')->default(false)->comment('Show ONP tracking numbers field');
            $table->boolean('enable_fishery_id')->default(false)->comment('Show fishery selection');
            $table->boolean('enable_ship_id')->default(false)->comment('Show ship selection');
            $table->boolean('enable_origin_id')->default(false)->comment('Show origin selection');
            $table->boolean('enable_truck_id')->default(false)->comment('Show truck selection');
            $table->boolean('enable_clients')->default(false)->comment('Show client selection');
            $table->boolean('enable_direction')->default(false)->comment('If true, shipping direction (local/export) field is shown');
            $table->boolean('enable_invoices')->default(false)->comment('If true, shippings of this category can be included in client invoices');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Types of sales/shippings. Has many shippings, detail_shipping_categories.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_categories');
    }
};
