<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Field definitions for warehouse transfer line items.
        Schema::create('warehouse_transfer_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_fr')->nullable()->comment('French name');
            $table->string('name_en')->nullable()->comment('English name');
            $table->string('name_es')->nullable()->comment('Spanish name');
            $table->text('description')->nullable()->comment('Description');
            $table->string('article_type')->default('text')->comment('Type of item: text, FishType, GuanoType, or PackageType');
            $table->enum('fish_type_filter', ['all', 'fresh', 'frozen'])->default('all')->comment('Filter fish types');
            $table->boolean('allow_weight')->default(false)->comment('Show weight field');
            $table->boolean('allow_package_type')->default(false)->comment('Show package type selection');
            $table->boolean('allow_nbr_box')->default(false)->comment('Show number of boxes');
            $table->boolean('allow_warehouse')->default(false)->comment('Show warehouse selection');
            $table->boolean('allow_trucks')->default(false)->comment('Show truck selection');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Field definitions for transfer line items. Has many detail_warehouse_transfers.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfer_categories');
    }
};
