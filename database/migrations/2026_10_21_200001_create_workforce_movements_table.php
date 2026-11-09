<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workforce_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workforce_id')->constrained('workforces')->cascadeOnDelete();
            $table->string('type'); // absence, departure, return, assignment
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('days_count')->default(0);
            $table->string('reason')->nullable(); // motif
            $table->text('notes')->nullable();
            $table->boolean('restored_teams')->default(false);
            $table->json('previous_team_ids')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workforce_id', 'type']);
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workforce_movements');
    }
};

