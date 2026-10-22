<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'is_super_admin', 'permissions'];

    protected $casts = [
        'is_super_admin' => 'boolean',
        'permissions' => 'array',
    ];

    /**
     * Check if this role has a specific permission.
     * Super admins bypass all checks.
     */
    public function hasPermission(string $key): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $perms = $this->permissions ?? [];

        // Master-key patterns: <module>.manage grants all <module>.* sub-permissions
        $masterKeyModules = ['settings', 'workforce', 'supplier_invoices', 'client_invoices', 'quality_control', 'users'];
        foreach ($masterKeyModules as $module) {
            if (str_starts_with($key, $module . '.') && $key !== $module . '.manage') {
                if (in_array($module . '.manage', $perms, true)) {
                    return true;
                }
            }
        }

        return in_array($key, $perms, true);
    }

    /**
     * Get all available permission keys in the system.
     */
    public static function allPermissionKeys(): array
    {
        return [
            // Core Analytics
            'dashboard.view',
            'user_statistics.view',
            'ai_prompt.view',

            // User & Role Management
            'users.manage',
            'users.roles',

            // Charges & Purchases
            'charges.view',
            'charges.create',
            'charges.edit',
            'charges.delete',

            // Production
            'production.view',
            'production.create',
            'production.edit',
            'production.delete',
            'production.charge_management',

            // Quality Control
            'quality_control.view',
            'quality_control.manage',
            'quality_control.create',
            'quality_control.edit',
            'quality_control.delete',

            // Shippings / Sales
            'shippings.view',
            'shippings.create',
            'shippings.edit',
            'shippings.delete',

            // Warehouses & Storage
            'warehouses.view',
            'warehouses.create',
            'warehouses.edit',
            'warehouses.delete',
            'warehouses.storage_invoices',
            'warehouses.sejour_rates',

            // Warehouse Transfers
            'warehouse_transfers.view',
            'warehouse_transfers.create',
            'warehouse_transfers.edit',
            'warehouse_transfers.delete',

            // Inventory
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'inventory.delete',

            // Workforce / Human Resources
            'workforce.view',
            'workforce.manage',
            'workforce.create',
            'workforce.edit',
            'workforce.delete',
            'workforce.corporals',
            'workforce.teams',
            'workforce.sections',
            'workforce.fortnight',
            'workforce.absence',
            'workforce.statistics',

            // Supplier Invoices & Payments
            'supplier_invoices.view',
            'supplier_invoices.manage',
            'supplier_invoices.create',
            'supplier_invoices.edit',
            'supplier_invoices.delete',
            'supplier_invoices.settlement',

            // Client Invoices & Payments
            'client_invoices.view',
            'client_invoices.manage',
            'client_invoices.create',
            'client_invoices.edit',
            'client_invoices.delete',
            'client_invoices.settlement',

            // Order Transfers
            'order_transfers.view',
            'order_transfers.create',
            'order_transfers.edit',
            'order_transfers.delete',

            // Settings & Master Parameters
            'settings.manage',
            'settings.warehouses',
            'settings.fish',
            'settings.charges',
            'settings.shippings',
            'settings.production',
            'settings.inventory_categories',
            'settings.transfer_categories',
            'settings.guano',
            'settings.packages',
            'settings.banks',
            'settings.company',
            'settings.fortnight',
            'settings.charts',
            'settings.static_charges',
            'settings.cost_configuration',
        ];
    }

    /**
     * Permission keys grouped by module for UI display.
     */
    public static function permissionGroups(): array
    {
        return [
            'dashboard' => ['dashboard.view'],
            'user_statistics' => ['user_statistics.view'],
            'ai_prompt' => ['ai_prompt.view'],
            'user_management' => ['users.manage', 'users.roles'],
            'charges' => [
                'charges.view',
                'charges.create',
                'charges.edit',
                'charges.delete',
            ],
            'production' => [
                'production.view',
                'production.create',
                'production.edit',
                'production.delete',
                'production.charge_management',
            ],
            'quality_control' => [
                'quality_control.manage',
                'quality_control.view',
                'quality_control.create',
                'quality_control.edit',
                'quality_control.delete',
            ],
            'shippings' => [
                'shippings.view',
                'shippings.create',
                'shippings.edit',
                'shippings.delete',
            ],
            'warehouses' => [
                'warehouses.view',
                'warehouses.create',
                'warehouses.edit',
                'warehouses.delete',
                'warehouses.storage_invoices',
                'warehouses.sejour_rates',
            ],
            'warehouse_transfers' => [
                'warehouse_transfers.view',
                'warehouse_transfers.create',
                'warehouse_transfers.edit',
                'warehouse_transfers.delete',
            ],
            'inventory' => [
                'inventory.view',
                'inventory.create',
                'inventory.edit',
                'inventory.delete',
            ],
            'workforce' => [
                'workforce.manage',
                'workforce.view',
                'workforce.create',
                'workforce.edit',
                'workforce.delete',
                'workforce.corporals',
                'workforce.teams',
                'workforce.sections',
                'workforce.fortnight',
                'workforce.absence',
                'workforce.statistics',
            ],
            'supplier_invoices' => [
                'supplier_invoices.manage',
                'supplier_invoices.view',
                'supplier_invoices.create',
                'supplier_invoices.edit',
                'supplier_invoices.delete',
                'supplier_invoices.settlement',
            ],
            'client_invoices' => [
                'client_invoices.manage',
                'client_invoices.view',
                'client_invoices.create',
                'client_invoices.edit',
                'client_invoices.delete',
                'client_invoices.settlement',
            ],
            'order_transfers' => [
                'order_transfers.view',
                'order_transfers.create',
                'order_transfers.edit',
                'order_transfers.delete',
            ],
            'settings' => [
                'settings.manage',
                'settings.warehouses',
                'settings.fish',
                'settings.charges',
                'settings.shippings',
                'settings.production',
                'settings.inventory_categories',
                'settings.transfer_categories',
                'settings.guano',
                'settings.packages',
                'settings.banks',
                'settings.company',
                'settings.fortnight',
                'settings.charts',
                'settings.static_charges',
                'settings.cost_configuration',
            ],
        ];
    }

    function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
