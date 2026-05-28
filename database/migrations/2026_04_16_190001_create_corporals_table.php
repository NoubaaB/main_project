<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Team leaders/supervisors who manage groups of workers.
        Schema::create('corporals', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->comment('Full name of the corporal/team leader');
            $table->enum('sex', ['male', 'female'])->default('male')->comment('Gender');
            $table->string('cni')->nullable()->comment('National ID card number (CNI)');
            $table->boolean('is_quinzaine')->default(false)->comment('Whether this corporal is paid per fortnight period');
            $table->decimal('daily_price', 10, 2)->default(0)->comment('Default daily price for this corporal');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Team leaders. BelongsToMany sections (section_corporals). Has many fortnight_activities. is_quinzaine=fortnight pay.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corporals');
    }
};
