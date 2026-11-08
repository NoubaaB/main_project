<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('charges', 'total_weight_invoice')) {
            Schema::table('charges', function (Blueprint $table) {
                $table->decimal('total_weight_invoice', 14, 2)->default(0)->after('total_weight')->comment('Sum of detail invoiced weights in KG');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('charges', 'total_weight_invoice')) {
            Schema::table('charges', function (Blueprint $table) {
                $table->dropColumn('total_weight_invoice');
            });
        }
    }
};

