<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_issues', function (Blueprint $table) {
            $table->id();
            $table->string('record_type')->comment('Model name: Charge, Production, Shipping');
            $table->unsignedBigInteger('record_id')->comment('ID of the record being flagged');
            $table->text('description')->comment('Issue description explaining the error');
            $table->string('status')->default('open')->comment('open, in_progress, resolved');
            $table->text('resolution_note')->nullable()->comment('Note explaining how the issue was resolved');
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete()->comment('User who reported the issue');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who resolved the issue');
            $table->timestamp('resolved_at')->nullable()->comment('When the issue was resolved');
            $table->timestamps();

            $table->index(['record_type', 'record_id']);
            $table->index('status');
            $table->index('reported_by');

            $table->comment('Issue flags on Charge, Production, Shipping records. Users can flag records with an error description to notify the creator/updater.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_issues');
    }
};
