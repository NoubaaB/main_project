<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Withdrawal/consumption operations from warehouse balance entries.
        Schema::create('warehouse_balance_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_balance_id')->constrained('warehouse_balances')->cascadeOnDelete()->comment('FK to warehouse_balances - parent balance entry');
            $table->date('date')->comment('Date of the withdrawal');
            $table->decimal('weight', 14, 2)->default(0)->comment('Weight withdrawn in KG');
            $table->decimal('unit_price', 14, 2)->default(0)->comment('Unit price at time of withdrawal in MAD');
            $table->decimal('total_price', 14, 2)->default(0)->comment('Total price of withdrawal in MAD');
            $table->decimal('wap_override', 14, 2)->nullable()->comment('Manual WAP override if auto-calculation is bypassed');
            $table->decimal('package_count', 10, 2)->default(0)->comment('Number of packages withdrawn');
            $table->string('source_type')->nullable()->comment('Polymorphic source: DetailSell or DetailProduction');
            $table->unsignedBigInteger('source_id')->nullable()->comment('Polymorphic ID of the consuming detail record');
            $table->string('client_name')->nullable()->comment('Cached client name for display');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Withdrawals from warehouse_balances. BelongsTo warehouse_balances. Polymorphic source.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_balance_operations');
    }
};

