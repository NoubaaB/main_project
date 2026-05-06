<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Workers/employees tracked for fortnight management.
        Schema::create('workforces', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->comment('Full name of the worker');
            $table->string('matricule')->unique()->comment('Unique employee ID/registration number');
            $table->string('nationality')->nullable()->comment('Worker nationality');
            $table->enum('sex', ['male', 'female'])->default('male')->comment('Gender');
            $table->string('cni')->nullable()->comment('National ID card number (CNI)');
            $table->boolean('is_migrant')->default(false)->comment('Whether the worker is a migrant worker');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Workers/employees. Has many fortnight_activities. Tracked by unique matricule.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workforces');
    }
};
