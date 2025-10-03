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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string("name",50)->unique();
            $table->string("description",250)->nullable();
            $table->double("total_weight")->default(0.00);
            $table->double("total_price")->default(0.00);
            $table->double("total_package_count")->default(0.00);

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This stocks table describe the stock of fish after production and have relationship with production_details table as has many of production_detail records also have relationship with charge_details table as has many of charge_detail' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
