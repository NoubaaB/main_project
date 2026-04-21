<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sell_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sell_id')->constrained('sells')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 100);
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->json('changes')->nullable()->comment('JSON: { field: { old: ..., new: ... } }');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sell_audit_logs');
    }
};
