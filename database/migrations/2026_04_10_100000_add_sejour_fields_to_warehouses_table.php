<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->boolean('has_sejour')->default(false)->after('warehouse_type')
                  ->comment('Whether this warehouse charges storage stay (séjour) fees');
            $table->decimal('operation_service_fee', 10, 2)->default(0.00)->after('has_sejour')
                  ->comment('Handling fee per operation, charged x2 (entry + exit)');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['has_sejour', 'operation_service_fee']);
        });
    }
};
