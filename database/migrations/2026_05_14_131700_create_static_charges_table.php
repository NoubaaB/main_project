<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_charges', function (Blueprint $table) {
            $table->id();
            $table->string('name_en')->nullable();
            $table->string('name_fr')->nullable();
            $table->string('name_es')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('date_range_type', ['all_time', 'date_range', 'ongoing'])->default('all_time');
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_charges');
    }
};
