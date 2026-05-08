<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_transfer_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_transfer_id')->constrained('order_transfers')->cascadeOnDelete();
            $table->string('beneficiary_type'); // 'supplier' or 'client'
            $table->unsignedBigInteger('beneficiary_id');
            $table->timestamps();

            $table->index(['beneficiary_type', 'beneficiary_id'], 'otb_type_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_transfer_beneficiaries');
    }
};
