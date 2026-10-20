<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_stock_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->date('reset_date')->comment('Date at which warehouse stock was initialized/reset to 0');
            $table->text('reason')->nullable()->comment('Reason or audit note for stock reset');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('total_weight_cleared', 14, 2)->default(0);
            $table->decimal('total_price_cleared', 14, 2)->default(0);
            $table->integer('articles_count')->default(0);
            $table->integer('balances_affected_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'reset_date'], 'wsr_wh_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock_resets');
    }
};
