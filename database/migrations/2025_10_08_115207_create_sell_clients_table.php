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
        Schema::create('sell_clients', function (Blueprint $table) {
            $table->foreignId('sell_id')->nullable();
            $table->foreignId('client_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('This sell_clients table describe the many to many relationship between sells and clients tables' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sell_clients');
    }
};
