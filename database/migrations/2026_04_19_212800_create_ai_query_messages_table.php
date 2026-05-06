<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AI Query (analytics/charts) chat message history.
        Schema::create('ai_query_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('FK to users - message author');
            $table->string('role')->comment('Message sender: user or agent');
            $table->longText('text')->comment('Message content (Vue/HTML for agent responses)');
            $table->string('reaction')->nullable()->comment('User reaction: like, dislike, or null');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('AI Query (analytics) chat history. BelongsTo users.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_query_messages');
    }
};
