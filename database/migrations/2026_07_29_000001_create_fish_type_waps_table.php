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
        Schema::dropIfExists('fish_type_waps');
        Schema::create('fish_type_waps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fish_type_id')->constrained('fish_types')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->date('date_start');
            $table->date('date_end')->nullable()->comment('Null means indefinite from date_start onwards');
            $table->decimal('wap', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['fish_type_id', 'warehouse_id', 'date_start', 'date_end'], 'ft_waps_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fish_type_waps');
    }
};
