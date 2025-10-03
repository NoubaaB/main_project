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
        Schema::create('charge_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')->nullable();
            $table->foreignId('supplier_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This charge_suppliers table describe the many to many relationship between charges and suppliers tables' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charge_suppliers');
    }
};
