<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AI Assistant (CRUD operations) chat message history.
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('FK to users - message author');
            $table->enum('role', ['user', 'agent'])->comment('Message sender: user or AI agent');
            $table->longText('text')->comment('Message content (HTML for agent responses)');
            $table->string('reaction')->nullable()->comment('User reaction: like, dislike, or null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
            $table->comment('AI Assistant (CRUD) chat history. BelongsTo users.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
