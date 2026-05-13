<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workforces', function (Blueprint $table) {
            $table->boolean('salary_statement')->default(false)->after('is_migrant')->comment('Whether this worker has a salary statement (bulletin de paie)');
            $table->string('cnss_id', 100)->nullable()->after('salary_statement')->comment('CNSS registration number');
        });
    }

    public function down(): void
    {
        Schema::table('workforces', function (Blueprint $table) {
            $table->dropColumn(['salary_statement', 'cnss_id']);
        });
    }
};
