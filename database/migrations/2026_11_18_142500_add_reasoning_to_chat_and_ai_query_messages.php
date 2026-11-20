<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_messages') && !Schema::hasColumn('chat_messages', 'reasoning')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->longText('reasoning')->nullable()->after('text')->comment('AI Agent reasoning & thought process JSON');
            });
        }

        if (Schema::hasTable('ai_query_messages') && !Schema::hasColumn('ai_query_messages', 'reasoning')) {
            Schema::table('ai_query_messages', function (Blueprint $table) {
                $table->longText('reasoning')->nullable()->after('text')->comment('AI Agent reasoning & thought process JSON');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chat_messages') && Schema::hasColumn('chat_messages', 'reasoning')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->dropColumn('reasoning');
            });
        }

        if (Schema::hasTable('ai_query_messages') && Schema::hasColumn('ai_query_messages', 'reasoning')) {
            Schema::table('ai_query_messages', function (Blueprint $table) {
                $table->dropColumn('reasoning');
            });
        }
    }
};

