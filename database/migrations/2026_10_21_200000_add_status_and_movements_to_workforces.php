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
        Schema::table('workforces', function (Blueprint $table) {
            $table->string('status')->default('active')->after('daily_price'); // active, absent, left_company
            $table->date('departure_date')->nullable()->after('status');
            $table->string('departure_reason')->nullable()->after('departure_date');
            $table->date('return_date')->nullable()->after('departure_reason');
            $table->json('previous_team_ids')->nullable()->after('return_date');
        });

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workforce_movements');

        Schema::table('workforces', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'departure_date',
                'departure_reason',
                'return_date',
                'previous_team_ids',
            ]);
        });
    }
};
