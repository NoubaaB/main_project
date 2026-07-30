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
        Schema::create('package_secondary_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_type_id')->constrained('package_types')->cascadeOnDelete();
            $table->date('date_start');
            $table->date('date_end')->nullable()->comment('Null means indefinite');
            
            // 6 Secondary Packaging components (qte and unit price)
            $table->decimal('caisses_qte', 10, 4)->default(0);
            $table->decimal('caisses_price', 10, 4)->default(0);
            
            $table->decimal('cellophane_qte', 10, 4)->default(0);
            $table->decimal('cellophane_price', 10, 4)->default(0);
            
            $table->decimal('plastique_qte', 10, 4)->default(0);
            $table->decimal('plastique_price', 10, 4)->default(0);
            
            $table->decimal('strapex_qte', 10, 4)->default(0);
            $table->decimal('strapex_price', 10, 4)->default(0);
            
            $table->decimal('palletes_qte', 10, 4)->default(0);
            $table->decimal('palletes_price', 10, 4)->default(0);
            
            $table->decimal('scotch_qte', 10, 4)->default(0);
            $table->decimal('scotch_price', 10, 4)->default(0);
            
            $table->decimal('unit_cost', 12, 4)->default(0)->comment('Sum of (qte * price) for all 6 items per package unit');

            $table->timestamps();
            $table->softDeletes();
            
            $table->comment('Secondary Packaging configuration and cost rules per package type and date range.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_secondary_configs');
    }
};
