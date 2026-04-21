<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_query_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role');          // 'user' or 'agent'
            $table->longText('text');
            $table->string('reaction')->nullable(); // 'like', 'dislike', or null
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_query_messages');
    }
};
