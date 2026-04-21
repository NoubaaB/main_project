<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')->unique()->constrained('charges')->onDelete('cascade');
            $table->foreignId('production_category_id')->constrained('production_categories')->onDelete('cascade');
            $table->date('date');
            $table->string('note')->nullable();
            $table->json('reception_tracking_number')->default('[]');
            $table->json('tracking_onp_numbers')->default('[]');
            $table->json('production_tracking_numbers')->default('[]');
            $table->foreignId('fishery_id')->nullable()->constrained('fisheries')->nullOnDelete();
            $table->foreignId('origin_id')->nullable()->constrained('origins')->nullOnDelete();
            $table->decimal('total_weight', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->decimal('total_package_count', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Pivot tables
        Schema::create('production_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
        });

        Schema::create('production_truck', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->onDelete('cascade');
            $table->foreignId('truck_id')->constrained('trucks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_truck');
        Schema::dropIfExists('production_supplier');
        Schema::dropIfExists('productions');
    }
};
