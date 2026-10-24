<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'action',
        'record_id',
        'data',
        'actor_name',
        'actor_id',
        'permission_key',
        'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function (Notification $notification) {
            try {
                if ($notification->user_id) {
                    $webPush = app(\App\Services\WebPushService::class);
                    $snapshot = is_array($notification->data) ? $notification->data : (json_decode($notification->data, true) ?: []);
                    $payload = $webPush->buildModelPayload(
                        $notification->type ?: 'Notification',
                        $notification->action ?: 'created',
                        (int)$notification->record_id,
                        $notification->actor_name ?: 'System',
                        $snapshot
                    );
                    $webPush->sendToUser($notification->user_id, $payload);
                }
            } catch (\Throwable $e) {
                \Log::warning('[Notification] WebPush dispatch failed on created: ' . $e->getMessage());
            }
        });
    }

    /* ── Relationships ─────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ── Scopes ────────────────────────────────────── */

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /* ── Helpers ───────────────────────────────────── */

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Map model names to the required permission key.
     * Only these models generate user-facing notifications.
     */
    public static function permissionMap(): array
    {
        return [
            'Charge'             => 'charges.view',
            'Production'         => 'production.view',
            'Shipping'           => 'shippings.view',
            'Inventory'          => 'inventory.view',
            'WarehouseTransfer'  => 'warehouse_transfers.view',
            'SupplierInvoice'    => 'supplier_invoices.view',
            'ClientInvoice'      => 'client_invoices.view',
            'StorageInvoice'     => 'warehouses.view',
            'OrderTransfer'      => 'supplier_invoices.view',
            'FortnightActivity'  => 'workforce.view',
            'CostConfiguration'  => 'settings.charges',
            'RecordIssue'        => '',
        ];
    }

    /**
     * Map model names → frontend route name for navigation.
     */
    public static function routeMap(): array
    {
        return [
            'Charge'             => 'view_charge',
            'Production'         => 'view_production',
            'Shipping'           => 'view_shipping',
            'Inventory'          => 'edit_inventory',
            'WarehouseTransfer'  => 'edit_warehouse_transfer',
            'SupplierInvoice'    => 'list_supplier_invoice',
            'ClientInvoice'      => 'list_client_invoice',
            'StorageInvoice'     => 'storage_invoices',
            'OrderTransfer'      => 'order_transfers_page',
            'FortnightActivity'  => 'fortnight_management',
            'CostConfiguration'  => 'cost_configuration_management',
            'RecordIssue'        => '',
        ];
    }
}
