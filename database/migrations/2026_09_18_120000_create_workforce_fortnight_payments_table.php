<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workforce_fortnight_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workforce_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('fortnight_period')->nullable()->comment('e.g. first_half, second_half');
            $table->boolean('is_paid')->default(false);
            $table->timestamps();

            $table->unique(['workforce_id', 'year', 'month', 'fortnight_period'], 'wfp_unique');
            $table->index(['year', 'month']);
            $table->comment('Tracks fortnight payment status per workforce per month.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workforce_fortnight_payments');
    }
};
