<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Fish types are individual species used as polymorphic stockable items throughout the system.
        Schema::create('fish_types', function (Blueprint $table) {
            $table->id();
            $table->string("name_fr")->unique()->comment('French name of the fish species');
            $table->string("name_en")->unique()->comment('English name of the fish species');
            $table->string("name_es")->unique()->comment('Spanish name of the fish species');
            $table->string("name_in_invoice")->unique()->comment('Name displayed on printed invoices');
            $table->string("scientific_name")->nullable()->comment('Scientific/Latin name of the species');
            $table->string("image")->nullable()->comment('Path to fish species image');
            $table->string("description")->nullable()->comment('Optional description or notes');
            $table->string("process")->nullable()->comment('Default processing method for this species');
            $table->enum("type", ["fresh", "frozen"])->nullable()->comment('Fish state: fresh or frozen');

            $table->foreignId('fish_family_id')->nullable()->index()->comment('FK to fish_families - the family this species belongs to');
            $table->foreignId('fish_process_type_id')->nullable()->index()->comment('FK to fish_process_types - default processing type');
            $table->string('productivity_color')->nullable()->comment('Color code for productivity chart display');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Fish species (sardine, mackerel). BelongsTo fish_families, fish_process_types. Polymorphic stockable in detail_charges, detail_productions, detail_shippings, warehouse_balances.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_types');
    }
};
