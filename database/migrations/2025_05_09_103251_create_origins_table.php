<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Origins represent geographic source regions for fish.
        Schema::create('origins', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique()->comment('Origin name (e.g., Atlantic Coast, Mediterranean)');
            $table->string("description")->nullable()->comment('Additional description');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Geographic origins of fish. Referenced by charges, productions, shippings.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('origins');
    }
};
