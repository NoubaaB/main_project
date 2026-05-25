<?php

namespace App\Traits;

use App\Events\ModelChanged;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Broadcasts a ModelChanged event for real-time sync across clients.
 * Also creates persisted notifications and audit log entries.
 */
trait BroadcastsModelChanges
{
    /**
     * Broadcast a model change + persist notifications + audit log.
     *
     * @param string $model      e.g. 'Charge'
     * @param string $action     e.g. 'created', 'updated', 'deleted'
     * @param int    $recordId
     * @param array  $recordData The record's current data (empty on delete)
     * @param array  $oldData    Previous data (for audit trail on updates)
     */
    protected function broadcastChange(string $model, string $action, int $recordId, array $recordData = [], array $oldData = []): void
    {
        try {
            $user = Auth::user();
            $userName = $user?->name ?? 'System';
            $userId = $user?->id;

            $permissionMap = Notification::permissionMap();
            $permissionKey = $permissionMap[$model] ?? '';

            // Broadcast via WebSocket
            broadcast(new ModelChanged(
                $model, $action, $recordId, $userName, $recordData, $userId, $permissionKey
            ))->toOthers();

            // Persist notifications for all eligible users (only for tracked modules)
            if ($permissionKey) {
                $this->createNotifications($model, $action, $recordId, $recordData, $userName, $userId, $permissionKey);
            }

            // Audit log (for all tracked modules)
            if ($permissionKey) {
                $this->createAuditLog($model, $action, $recordId, $userName, $userId, $oldData, $recordData);
            }
        } catch (\Throwable $e) {
            // Silently fail if broadcasting is not configured
            \Log::warning('[BroadcastsModelChanges] Failed: ' . $e->getMessage());
        }
    }

    /**
     * Create notification records for all users with the required permission.
     * Excludes the actor (they already know what they did).
     */
    private function createNotifications(
        string $model, string $action, int $recordId,
        array $data, string $actorName, ?int $actorId, string $permissionKey
    ): void {
        try {
            // Build a slim data snapshot for the notification
            $snapshot = $this->buildNotificationSnapshot($model, $data);

            // Get all active users except the actor
            $query = User::where('is_active', true);
            if ($actorId) {
                $query->where('id', '!=', $actorId);
            }
            $users = $query->with('role')->get();

            $notifications = [];
            $now = now();
            foreach ($users as $user) {
                // Check if user has permission to see this module
                if (!$user->hasPermission($permissionKey)) {
                    continue;
                }

                $notifications[] = [
                    'user_id'        => $user->id,
                    'type'           => $model,
                    'action'         => $action,
                    'record_id'      => $recordId,
                    'data'           => json_encode($snapshot),
                    'actor_name'     => $actorName,
                    'actor_id'       => $actorId,
                    'permission_key' => $permissionKey,
                    'read_at'        => null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            if (!empty($notifications)) {
                // Bulk insert for performance
                Notification::insert($notifications);
            }
        } catch (\Throwable $e) {
            \Log::warning('[BroadcastsModelChanges] Failed to create notifications: ' . $e->getMessage());
        }
    }

    /**
     * Build a compact snapshot for the notification data field.
     */
    private function buildNotificationSnapshot(string $model, array $data): array
    {
        $snapshot = [];

        // Common fields
        if (isset($data['date'])) $snapshot['date'] = $data['date'];

        switch ($model) {
            case 'Charge':
                if (isset($data['reception_tracking_number'])) {
                    $trackNums = $data['reception_tracking_number'];
                    $snapshot['tracking'] = is_array($trackNums) ? implode(', ', $trackNums) : $trackNums;
                }
                if (isset($data['total_weight'])) $snapshot['weight'] = $data['total_weight'];
                if (isset($data['total_price'])) $snapshot['price'] = $data['total_price'];
                if (isset($data['charge_category']['name_fr'])) $snapshot['category'] = $data['charge_category']['name_fr'];
                if (isset($data['supplier']['name'])) $snapshot['supplier'] = $data['supplier']['name'];
                break;

            case 'Production':
                if (isset($data['production_tracking_numbers'])) {
                    $trackNums = $data['production_tracking_numbers'];
                    $snapshot['tracking'] = is_array($trackNums) ? implode(', ', $trackNums) : $trackNums;
                }
                if (isset($data['total_fresh_weight'])) $snapshot['weight'] = $data['total_fresh_weight'];
                if (isset($data['production_category']['name_fr'])) $snapshot['category'] = $data['production_category']['name_fr'];
                break;

            case 'Shipping':
                if (isset($data['shipping_tracking_numbers'])) {
                    $trackNums = $data['shipping_tracking_numbers'];
                    $snapshot['tracking'] = is_array($trackNums) ? implode(', ', $trackNums) : $trackNums;
                }
                if (isset($data['total_weight'])) $snapshot['weight'] = $data['total_weight'];
                if (isset($data['total_price'])) $snapshot['price'] = $data['total_price'];
                break;

            case 'SupplierInvoice':
            case 'ClientInvoice':
                if (isset($data['tracking_number'])) $snapshot['tracking'] = $data['tracking_number'];
                if (isset($data['total_price'])) $snapshot['price'] = $data['total_price'];
                break;

            case 'WarehouseTransfer':
                if (isset($data['tracking_number'])) $snapshot['tracking'] = $data['tracking_number'];
                break;

            case 'Inventory':
                if (isset($data['tracking_number'])) $snapshot['tracking'] = $data['tracking_number'];
                if (isset($data['total_weight'])) $snapshot['weight'] = $data['total_weight'];
                break;
        }

        return $snapshot;
    }

    /**
     * Record an audit log entry for a model change.
     */
    private function createAuditLog(
        string $model, string $action, int $recordId,
        string $userName, ?int $userId,
        array $oldData, array $newData
    ): void {
        try {
            // For updates, compute only the changed fields
            $oldValues = null;
            $newValues = null;

            if ($action === 'updated' && !empty($oldData) && !empty($newData)) {
                $changed = $this->computeChangedFields($oldData, $newData);
                if (empty($changed['old']) && empty($changed['new'])) {
                    return; // No real changes
                }
                $oldValues = $changed['old'];
                $newValues = $changed['new'];
            } elseif ($action === 'created') {
                $newValues = $this->filterAuditFields($newData);
            } elseif ($action === 'deleted') {
                $oldValues = $this->filterAuditFields($oldData ?: $newData);
            }

            AuditLog::create([
                'user_id'        => $userId,
                'user_name'      => $userName,
                'auditable_type' => $model,
                'auditable_id'   => $recordId,
                'action'         => $action,
                'old_values'     => $oldValues,
                'new_values'     => $newValues,
                'created_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('[BroadcastsModelChanges] Failed to create audit log: ' . $e->getMessage());
        }
    }

    /**
     * Compute only the fields that actually changed between old and new data.
     */
    private function computeChangedFields(array $old, array $new): array
    {
        // Fields to ignore in audit diff
        $ignore = ['updated_at', 'created_at', 'deleted_at', 'detail_charges', 'detail_shippings',
                    'detail_productions', 'detail_inventories', 'detail_warehouse_transfers',
                    'charge_category', 'shipping_category', 'production_category', 'supplier',
                    'trucks', 'fishery', 'ship', 'origin', 'production', 'supplier_invoice',
                    'client', 'warehouse', 'stockable', 'package_type', 'category'];

        $changedOld = [];
        $changedNew = [];

        foreach ($new as $key => $value) {
            if (in_array($key, $ignore)) continue;
            $oldVal = $old[$key] ?? null;
            if ($oldVal != $value) {
                $changedOld[$key] = $oldVal;
                $changedNew[$key] = $value;
            }
        }

        return ['old' => $changedOld, 'new' => $changedNew];
    }

    /**
     * Filter out relationship arrays and metadata from audit data.
     */
    private function filterAuditFields(array $data): array
    {
        $ignore = ['detail_charges', 'detail_shippings', 'detail_productions',
                    'detail_inventories', 'detail_warehouse_transfers',
                    'charge_category', 'shipping_category', 'production_category',
                    'supplier', 'trucks', 'fishery', 'ship', 'origin', 'production',
                    'supplier_invoice', 'client', 'warehouse', 'stockable', 'package_type',
                    'category', 'deleted_at'];

        return array_diff_key($data, array_flip($ignore));
    }
}
