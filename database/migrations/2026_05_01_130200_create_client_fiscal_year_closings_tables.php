<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_fiscal_year_closings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique()->comment('Fiscal year');
            $table->date('closing_date')->nullable()->comment('Date when the year was closed');
            $table->decimal('total_invoices', 14, 2)->default(0)->comment('Total client invoices for the year');
            $table->decimal('total_payments', 14, 2)->default(0)->comment('Total client payments for the year');
            $table->decimal('carried_forward', 14, 2)->default(0)->comment('Balance carried forward from previous year');
            $table->boolean('is_closed')->default(false)->comment('Whether fiscal year is closed');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Client fiscal year closing records.');
        });

        Schema::create('client_fiscal_year_closing_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_fiscal_year_closing_id')->comment('FK to client_fiscal_year_closings');
            $table->foreign('client_fiscal_year_closing_id', 'cfy_closing_id_fk')->references('id')->on('client_fiscal_year_closings')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete()->comment('FK to clients; the client this line belongs to');
            $table->decimal('total_invoices', 14, 2)->default(0)->comment('Total invoices for this client in the year');
            $table->decimal('total_payments', 14, 2)->default(0)->comment('Total payments for this client in the year');
            $table->decimal('carry_forward', 14, 2)->default(0)->comment('Carry forward from previous year for this client');
            $table->decimal('balance', 14, 2)->default(0)->comment('Computed end-of-year balance');
            $table->timestamps();

            $table->unique(['client_fiscal_year_closing_id', 'client_id'], 'cfy_lines_closing_client_unique');
            $table->comment('Per-client closing line for client fiscal year closings.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_fiscal_year_closing_lines');
        Schema::dropIfExists('client_fiscal_year_closings');
    }
};
