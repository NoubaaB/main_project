<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'can_select_ai_model')) {
                $table->boolean('can_select_ai_model')->nullable()->default(null)->after('allowed_ai_crud')
                    ->comment('Nullable boolean: null=inherit from role permission, true=allowed, false=forbidden');
            }
            if (!Schema::hasColumn('users', 'allowed_ai_models')) {
                $table->json('allowed_ai_models')->nullable()->after('can_select_ai_model')
                    ->comment('JSON array of allowed model IDs or null for all');
            }
            if (!Schema::hasColumn('users', 'default_ai_model')) {
                $table->string('default_ai_model', 100)->nullable()->default('gemini-4-carbon')->after('allowed_ai_models')
                    ->comment('Default AI model assigned to this user');
            }
        });

        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'allowed_ai_models')) {
                $table->json('allowed_ai_models')->nullable()->after('permissions')
                    ->comment('JSON array of allowed model IDs for this role or null for all');
            }
            if (!Schema::hasColumn('roles', 'default_ai_model')) {
                $table->string('default_ai_model', 100)->nullable()->default('gemini-4-carbon')->after('allowed_ai_models')
                    ->comment('Default AI model assigned to this role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('users', 'can_select_ai_model')) $cols[] = 'can_select_ai_model';
            if (Schema::hasColumn('users', 'allowed_ai_models')) $cols[] = 'allowed_ai_models';
            if (Schema::hasColumn('users', 'default_ai_model')) $cols[] = 'default_ai_model';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('roles', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('roles', 'allowed_ai_models')) $cols[] = 'allowed_ai_models';
            if (Schema::hasColumn('roles', 'default_ai_model')) $cols[] = 'default_ai_model';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};

