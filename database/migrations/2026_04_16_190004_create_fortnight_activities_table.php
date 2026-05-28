<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daily work assignments for workers in a fortnight period.
        // Either section_id OR corporal_id is set, never both.
        Schema::create('fortnight_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workforce_id')->comment('FK to workforces - the assigned worker');
            $table->date('date')->comment('Work date');
            $table->enum('fortnight_period', ['Q1', 'Q2'])->comment('Q1=1st-15th of month, Q2=16th-end of month');
            $table->foreignId('section_id')->nullable()->comment('FK to sections - set when worker is assigned to a section (corporal_id must be null)');
            $table->foreignId('corporal_id')->nullable()->comment('FK to corporals - set when worker is assigned to a corporal (section_id must be null)');
            $table->decimal('price', 10, 2)->default(0.00)->comment('Daily payment amount in MAD');
            $table->boolean('is_fixed_price')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workforce_id', 'fortnight_period']);
            $table->index(['workforce_id', 'date']);
            $table->comment('Daily work assignments. BelongsTo workforces, sections, corporals. Q1=1st-15th, Q2=16th-end. Either section_id OR corporal_id set.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortnight_activities');
    }
};
