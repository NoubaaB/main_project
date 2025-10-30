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
        Schema::create('warehouse_categories', function (Blueprint $table) {
            $table->id();
            $table->string("name",50)->unique();
            $table->string("description",250)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This table stores categories for warehouses, allowing for organized classification of various warehouse types such as local, foreign, and client warehouses.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_categories');
    }
};
