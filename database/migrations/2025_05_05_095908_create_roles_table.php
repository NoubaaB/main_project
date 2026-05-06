<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User roles define access levels and permissions within the application.
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string("name", 50)->comment('Role name (e.g., admin, manager, operator)');

            $table->timestamps();
            $table->softDeletes();

            $table->comment('User roles (admin, manager, operator). Has many users.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
