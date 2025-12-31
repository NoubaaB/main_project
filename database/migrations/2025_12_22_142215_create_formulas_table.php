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
        Schema::create('formulas', function (Blueprint $table) {
            $table->id();
            $table->string("name_fr")->unique();
            $table->string("name_en")->unique();
            $table->string("name_es")->unique();
            $table->text('description')->nullable();
            $table->json('expressions'); /*"expressions": "(cc1_price + cc2_price) / cc3_weight * 1000",
                                            "chart_config" : [
                                                "chart_color" : "#330099ff",
                                                "chart_type" : "column",
                                            ],
                                            "variables": [
                                                {
                                                    "alias": "cc1_price",
                                                    "kind": "charge",            // "charge" or "sell"
                                                    "category_id": 1,
                                                    "stockable_type": "App/Models/Guano",
                                                    "stockable_id": "Guano Entier",
                                                    "field": "total_price"      // "total_price" | "total_weight" | "total_package_count"
                                                }
                                            ]
            */
            $table->boolean('enabled')->default(true);
            $table->enum('type', ['charge', 'sell'])->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formulas');
    }
};
