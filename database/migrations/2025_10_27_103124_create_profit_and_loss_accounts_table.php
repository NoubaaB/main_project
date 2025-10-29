<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profit_and_loss_accounts', function (Blueprint $table) {
            $table->id();

            // 🔹 Period Information
            $table->date('date')->nullable();
            $table->string('fiscal_year')->nullable(); // e.g. 2025
            $table->string('period_label')->nullable(); // e.g. "Q1 2025", "Jan 2025"

            // 🔹 Revenue Section
            $table->decimal('sales_revenue', 15, 2)->default(0);
            $table->decimal('other_income', 15, 2)->default(0);
            $table->decimal('total_revenue', 15, 2)->virtualAs('sales_revenue + other_income');

            // 🔹 Expense Section
            $table->decimal('cost_of_goods_sold', 15, 2)->default(0);
            $table->decimal('operating_expenses', 15, 2)->default(0);
            $table->decimal('administrative_expenses', 15, 2)->default(0);
            $table->decimal('marketing_expenses', 15, 2)->default(0);
            $table->decimal('financial_expenses', 15, 2)->default(0);
            $table->decimal('tax_expenses', 15, 2)->default(0);
            $table->decimal('total_expenses', 15, 2)->virtualAs('cost_of_goods_sold + operating_expenses + administrative_expenses + marketing_expenses + financial_expenses + tax_expenses');

            // 🔹 Profit Calculation
            $table->decimal('gross_profit', 15, 2)->virtualAs('total_revenue - cost_of_goods_sold');
            $table->decimal('net_profit', 15, 2)->virtualAs('total_revenue - total_expenses');

            // 🔹 Relationships
            $table->nullableMorphs("accountable");

            // 🔹 Metadata
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profit_and_loss_accounts');
    }
};
