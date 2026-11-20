<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User roles define access levels and permissions within the application.
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string("name", 50)->comment('Role name (e.g., admin, manager, operator)');
            $table->boolean('is_super_admin')->default(false);
            $table->json('permissions')->default('[]');
            $table->json('allowed_ai_models')->nullable()
                ->comment('JSON array of allowed model IDs for this role or null for all');
            $table->string('default_ai_model', 100)->nullable()->default('gemini-4-carbon')
                ->comment('Default AI model assigned to this role');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('User roles (admin, manager, operator). Has many users.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
