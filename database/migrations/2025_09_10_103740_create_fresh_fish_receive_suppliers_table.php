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
        Schema::create('fresh_fish_receive_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fresh_fish_receive_id')->nullable();
            $table->foreignId('supplier_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fresh_fish_receive_suppliers');
    }
};
