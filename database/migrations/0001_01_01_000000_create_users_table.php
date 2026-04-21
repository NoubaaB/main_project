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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('email', 50)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('role_id')->nullable()->index();
            $table->json('allowed_ai_crud')->default(
                json_encode([
                    'Section' => ['create', 'read', 'update', 'delete'],
                    'Activity' => ['create', 'read', 'update', 'delete'],
                    'Workforce' => ['create', 'read', 'update', 'delete'],
                    'Corporal' => ['create', 'read', 'update', 'delete'],
                    'FortnightActivity' => ['create', 'read', 'update', 'delete'],
                    'Charge' => ['create', 'read', 'update', 'delete'],
                    'Payment' => ['create', 'read', 'update', 'delete'],
                    'Supplier' => ['create', 'read', 'update', 'delete'],
                    'Ship' => ['create', 'read', 'update', 'delete'],
                ])
            );
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            // Add a comment to the whole table
            $table->comment('This users table has belong to one role record in table roles');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
