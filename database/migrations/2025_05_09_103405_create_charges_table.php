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
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->date("date")->nullable();
            $table->json("tracking_numbers")->nullable();
            $table->json("tracking_onp_numbers")->nullable();
            
            $table->string("note",500)->nullable();
            $table->decimal("total_price",15,2)->default(0.00);
            $table->decimal("total_weight",15,2)->default(0.00);
            $table->decimal("total_package_count",15,2)->default(0.00);

            $table->boolean("allow_invoice")->default(true);// Whether this charge allows billing or not like guano with no name/supplier

            $table->foreignId('invoice_id')->nullable()->index();
            $table->foreignId('fishery_id')->nullable()->index();
            $table->foreignId('ship_id')->nullable()->index();
            $table->foreignId('origin_id')->nullable()->index();
            $table->foreignId('supplier_process_id')->nullable()->index()
            ->comment('References the suppliers table to identify the local that merchandise treated in.');
            $table->foreignId('charge_category_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This table records fish purchases. It calculates total price and weight in both tables stock_fish_tracking_number_operations and non_warehouse_charge_operations. It belongs to one supplier, one fishery, one origin, and one charge_category, and has many related with stock_fish_tracking_number_operations records and non_warehouse_charge_operations as well.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
