<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * All available permission keys in the system.
     */
    private function allPermissions(): array
    {
        return [
            'dashboard.view',
            'ai_prompt.view',
            'charges.view', 'charges.create', 'charges.edit', 'charges.delete',
            'production.view', 'production.create', 'production.edit', 'production.delete',
            'shippings.view', 'shippings.create', 'shippings.edit', 'shippings.delete',
            'warehouses.view',
            'warehouse_transfers.view', 'warehouse_transfers.create',
            'inventory.view', 'inventory.create',
            'workforce.view', 'workforce.manage',
            'supplier_invoices.view', 'supplier_invoices.create', 'supplier_invoices.manage',
            'client_invoices.view', 'client_invoices.create', 'client_invoices.manage',
            'quality_control.view', 'quality_control.manage',
            'settings.manage',
        ];
    }

    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('name');
            $table->json('permissions')->default('[]')->after('is_super_admin');
        });

        // Seed the existing "admin" role (id=1) as super admin with all permissions
        DB::table('roles')->where('id', 1)->update([
            'is_super_admin' => true,
            'permissions' => json_encode($this->allPermissions()),
        ]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['is_super_admin', 'permissions']);
        });
    }
};
