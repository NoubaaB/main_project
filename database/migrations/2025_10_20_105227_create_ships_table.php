<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ships/vessels used for fishing operations.
        Schema::create('ships', function (Blueprint $table) {
            $table->id();
            $table->string("name")->nullable()->comment('Ship/vessel name');
            $table->string("imo_number")->nullable()->comment('International Maritime Organization (IMO) unique vessel identifier');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Fishing vessels. Referenced by charges, productions, shippings.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ships');
    }
};
