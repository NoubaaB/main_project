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
        Schema::create('fixed_charges', function (Blueprint $table) {
            $table->id();
            $table->date("start_date")->nullable();
            $table->date("end_date")->nullable();
            $table->json("tracking_numbers")->nullable();

            $table->string("name", 50)->nullable();
            $table->string("description", 500)->nullable();
            $table->decimal("amount",15,2)->default(0.00);
            $table->enum("fixed_periodic_charge", ['daily', 'weekly', 'monthly', 'yearly'])
            ->nullable()->default(null);

            $table->foreignId('charge_category_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This table records fixed charges. It calculates total amount depends on fixed_periodic_charge multiplication to amount and give total amount as price. It belongs to one type_charge.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_charges');
    }
};
