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
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->date("date")->nullable();
            $table->enum("type_invoice",["local","export"])->nullable();
            $table->json("tracking_numbers")->nullable();
            $table->json("supplier_ids")->nullable(); //type array
            $table->decimal("total_price", 15, 2)->default(0.00);
            $table->decimal("total_weight", 15, 2)->default(0.00);
            $table->integer("print_counter")->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
