<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fish process types define how fish is processed (e.g., fresh whole, frozen fillet, salted).
        Schema::create('fish_process_types', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique()->comment('Processing method name');
            $table->string("description")->nullable()->comment('Description of the processing method');
            $table->string('productivity_color')->nullable()->comment('Color code for productivity chart display');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Fish processing methods. Has many fish_types.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_process_types');
    }
};
