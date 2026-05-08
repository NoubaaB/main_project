<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->date('date');
            $table->text('motif')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('currency', ['MAD', 'EUR', 'USD'])->default('MAD');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('date');
            $table->index('currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_transfers');
    }
};
