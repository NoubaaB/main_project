<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clients are buyers/customers who purchase fish products.
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique()->comment('Client company or individual name');
            $table->string("phone", 20)->nullable()->comment('Contact phone number');
            $table->string("ice", 50)->nullable()->comment('ICE tax identification number (Morocco)');
            $table->string("email", 50)->nullable()->comment('Contact email address');
            $table->string("address", 100)->nullable()->comment('Street address');
            $table->string("city", 50)->nullable()->comment('City');
            $table->string("country", 200)->nullable()->comment('Country');
            $table->string("postal_code", 10)->nullable()->comment('Postal/ZIP code');
            $table->string("website", 200)->nullable()->comment('Website URL');
            $table->string("description", 200)->nullable()->comment('Additional notes about the client');
            $table->string("logo_image", 200)->nullable()->comment('Path to client logo image');
            $table->boolean("active_status")->default(true)->comment('Whether the client is currently active');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Fish buyers. BelongsToMany shippings (shipping_client), productions (production_client). MorphOne profit_and_loss_accounts.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
