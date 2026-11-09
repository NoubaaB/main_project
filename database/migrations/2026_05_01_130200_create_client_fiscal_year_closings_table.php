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
    }

    public function down(): void
    {
        Schema::dropIfExists('client_fiscal_year_closings');
    }
};

