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
        Schema::create('sells', function (Blueprint $table) {
            $table->id();
            $table->date("date")->nullable();
            $table->json("tracking_numbers")->default("[]");
            $table->json("tracking_onp_numbers")->default("[]");
            $table->string("note", 500)->nullable();

            $table->decimal("total_price",15,2)->default(0.00);
            $table->decimal("total_weight",15,2)->default(0.00);
            $table->decimal("total_package_count",15,2)->default(0.00);

            $table->foreignId('fishery_id')->nullable()->index();
            $table->foreignId('ship_id')->nullable()->index();
            $table->foreignId('origin_id')->nullable()->index();
            $table->foreignId('sell_category_id')->nullable()->index();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This sells table describe the sell of fish store and have relationship with sell_details table as has many of sell_detail records' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sells');
    }
};
