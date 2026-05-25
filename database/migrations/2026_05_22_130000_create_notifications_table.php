<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type');              // Model name: 'Charge', 'Production', etc.
            $table->string('action');            // 'created', 'updated', 'deleted'
            $table->unsignedBigInteger('record_id');
            $table->json('data')->nullable();    // Snapshot of key info
            $table->string('actor_name');        // Who performed the action
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('permission_key');    // Required permission to see this
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['type', 'record_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
