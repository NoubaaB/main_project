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

        // settings.manage acts as a master key for all settings.* sub-permissions
        if (str_starts_with($key, 'settings.') && $key !== 'settings.manage') {
            if (in_array('settings.manage', $perms, true)) {
                return true;
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
            'dashboard.view',
            'ai_prompt.view',
            'users.manage', 'users.roles',
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
            'order_transfers.view',
            'user_statistics.view',
            'settings.manage',
            'settings.warehouses', 'settings.fish', 'settings.charges',
            'settings.shippings', 'settings.production',
            'settings.inventory_categories', 'settings.transfer_categories',
            'settings.guano', 'settings.packages', 'settings.banks',
            'settings.company', 'settings.fortnight',
            'settings.charts', 'settings.static_charges',
        ];
    }

    /**
     * Permission keys grouped by module for UI display.
     */
    public static function permissionGroups(): array
    {
        return [
            'dashboard' => ['dashboard.view'],
            'ai_prompt' => ['ai_prompt.view'],
            'user_management' => ['users.manage', 'users.roles'],
            'charges' => ['charges.view', 'charges.create', 'charges.edit', 'charges.delete'],
            'production' => ['production.view', 'production.create', 'production.edit', 'production.delete'],
            'shippings' => ['shippings.view', 'shippings.create', 'shippings.edit', 'shippings.delete'],
            'warehouses' => ['warehouses.view'],
            'warehouse_transfers' => ['warehouse_transfers.view', 'warehouse_transfers.create'],
            'inventory' => ['inventory.view', 'inventory.create'],
            'workforce' => ['workforce.view', 'workforce.manage'],
            'supplier_invoices' => ['supplier_invoices.view', 'supplier_invoices.create', 'supplier_invoices.manage'],
            'client_invoices' => ['client_invoices.view', 'client_invoices.create', 'client_invoices.manage'],
            'quality_control' => ['quality_control.view', 'quality_control.manage'],
            'order_transfers' => ['order_transfers.view'],
            'user_statistics' => ['user_statistics.view'],
            'settings' => [
                'settings.manage',
                'settings.warehouses', 'settings.fish', 'settings.charges',
                'settings.shippings', 'settings.production',
                'settings.inventory_categories', 'settings.transfer_categories',
                'settings.guano', 'settings.packages', 'settings.banks',
                'settings.company', 'settings.fortnight',
                'settings.charts', 'settings.static_charges',
            ],
        ];
    }

    function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
