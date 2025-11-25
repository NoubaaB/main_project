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
        Schema::create('package_types', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();
            $table->integer("size");
            $table->string("description")->nullable();
            $table->string("type_emballage")->nullable();
            

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This package_types table describe purchase package_types of fish_type its like emballage that contain fish and have relationship with production_details table as has many of production_detail records' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_types');
    }
};
