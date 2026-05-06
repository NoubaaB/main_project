<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Warehouse categories group warehouses by purpose.
        Schema::create('warehouse_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Category name');
            $table->text('description')->nullable()->comment('Category description');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Categories of warehouses. Has many warehouses.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_categories');
    }
};
