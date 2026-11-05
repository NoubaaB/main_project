<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('record_issues', function (Blueprint $table) {
            $table->foreignId('bon_creator_id')
                ->nullable()
                ->after('reported_by')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who created the bon being flagged (User B)');

            $table->string('bon_reference')
                ->nullable()
                ->after('record_id')
                ->comment('Human-readable bon identifier, e.g. Bon Charge #125 (REC-9942)');

            $table->date('bon_date')
                ->nullable()
                ->after('bon_reference')
                ->comment('Date of the bon');

            $table->index('bon_creator_id');
        });
    }

    public function down(): void
    {
        Schema::table('record_issues', function (Blueprint $table) {
            $table->dropForeign(['bon_creator_id']);
            $table->dropIndex(['bon_creator_id']);
            $table->dropColumn(['bon_creator_id', 'bon_reference', 'bon_date']);
        });
    }
};

