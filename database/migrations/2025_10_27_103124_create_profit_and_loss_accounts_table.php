<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // P&L accounting records with polymorphic link to suppliers or clients.
        Schema::create('profit_and_loss_accounts', function (Blueprint $table) {
            $table->id();

            // Period Information
            $table->date('date')->nullable()->comment('Accounting date for this P&L entry');
            $table->string('fiscal_year')->nullable()->comment('Fiscal year (e.g., 2025)');
            $table->string('period_label')->nullable()->comment('Period label (e.g., Q1 2025, Jan 2025)');

            // Revenue Section
            $table->decimal('sales_revenue', 15, 2)->default(0)->comment('Revenue from fish sales in MAD');
            $table->decimal('other_income', 15, 2)->default(0)->comment('Other income sources in MAD');
            $table->decimal('total_revenue', 15, 2)->virtualAs('sales_revenue + other_income')->comment('Computed: sales_revenue + other_income');

            // Expense Section
            $table->decimal('cost_of_goods_sold', 15, 2)->default(0)->comment('Cost of fish purchases in MAD');
            $table->decimal('operating_expenses', 15, 2)->default(0)->comment('Operating costs in MAD');
            $table->decimal('administrative_expenses', 15, 2)->default(0)->comment('Administrative costs in MAD');
            $table->decimal('marketing_expenses', 15, 2)->default(0)->comment('Marketing costs in MAD');
            $table->decimal('financial_expenses', 15, 2)->default(0)->comment('Financial costs (interest, fees) in MAD');
            $table->decimal('tax_expenses', 15, 2)->default(0)->comment('Tax expenses in MAD');
            $table->decimal('total_expenses', 15, 2)->virtualAs('cost_of_goods_sold + operating_expenses + administrative_expenses + marketing_expenses + financial_expenses + tax_expenses')->comment('Computed: sum of all expense fields');

            // Profit Calculation
            $table->decimal('gross_profit', 15, 2)->virtualAs('total_revenue - cost_of_goods_sold')->comment('Computed: total_revenue - cost_of_goods_sold');
            $table->decimal('net_profit', 15, 2)->virtualAs('total_revenue - total_expenses')->comment('Computed: total_revenue - total_expenses');

            // Polymorphic relationship
            $table->nullableMorphs("accountable");

            // Metadata
            $table->text('notes')->nullable()->comment('Additional accounting notes');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('P&L accounting. Polymorphic accountable (Supplier/Client). Virtual computed totals.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_and_loss_accounts');
    }
};
