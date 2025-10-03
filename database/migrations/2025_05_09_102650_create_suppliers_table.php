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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();
            $table->string("phone",20)->nullable();
            $table->string("ice",50)->nullable();
            $table->string("email",50)->nullable();
            $table->string("address",100)->nullable();
            $table->string("city",50)->nullable();
            $table->string("country",200)->nullable();
            $table->string("postal_code",10)->nullable();
            $table->string("website",200)->nullable();
            $table->string("description",200)->nullable();
            $table->string("logo_image",200)->nullable();
            $table->boolean("active_status")->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This suppliers table describe the provider that supply fish or other type of chargers and have relationship with charge table as has many of charges records');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
