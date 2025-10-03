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
        Schema::create('stock_details', function (Blueprint $table) {
            $table->id();
            $table->double("weight")->default(0.00);
            $table->double("pu")->default(0.00);
            $table->double("price")->default(0.00);

            $table->foreignId('stock_id')->nullable()->index();
            $table->unsignedBigInteger('detailable_id');
            $table->string('detailable_type');
            $table->foreignId('package_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This stock_details table describe each line in stocks table and have relationship with stocks table as belong to one stock record, packages table as belong to one package record, and its polymorphic relationship with production_details table or charge_details table as detailable' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_details');
    }
};
