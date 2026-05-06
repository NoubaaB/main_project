<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Work sections/departments where workers are assigned.
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Section/department name');
            $table->decimal('daily_price', 10, 2)->default(0.00)->comment('Standard daily rate for workers in this section (MAD)');
            $table->boolean('is_price_modifiable')->default(false)->comment('Whether the daily price can be overridden per activity');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Work departments. BelongsToMany corporals (section_corporals). Has many fortnight_activities. daily_price=standard rate.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
