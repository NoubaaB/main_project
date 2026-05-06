<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fiscal year closing records summarize the financial state at year-end.
        // Contains aggregated totals and the carry-forward balance for the next year.
        Schema::create('fiscal_year_closings', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique()->comment('The fiscal year being closed (e.g., 2026)');
            $table->date('closing_date')->comment('Date the fiscal year was officially closed');
            $table->decimal('total_invoices', 15, 2)->default(0.00)->comment('Total supplier invoice amount for the year');
            $table->decimal('total_payments', 15, 2)->default(0.00)->comment('Total supplier payment amount for the year');
            $table->decimal('carried_forward', 15, 2)->default(0.00)->comment('Outstanding balance carried forward to the next fiscal year');
            $table->boolean('is_closed')->default(true)->comment('Whether the fiscal year is currently closed (can be reopened)');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Fiscal year closing records with carry-forward balances. One record per year. Has many fiscal_year_closing_lines.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_year_closings');
    }
};
