<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    protected ?WebPush $webPush = null;

    public function __construct()
    {
        $this->initWebPush();
    }

    /**
     * Initialize the Minishlink WebPush client.
     */
    protected function initWebPush(): void
    {
        try {
            $vapid = [
                'subject'    => config('webpush.vapid.subject', 'mailto:admin@dev.local'),
                'publicKey'  => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ];

            if (empty($vapid['publicKey']) || empty($vapid['privateKey'])) {
                Log::warning('[WebPushService] VAPID keys not configured properly.');
                return;
            }

            $defaultOptions = config('webpush.default_options', [
                'TTL'     => 86400,
                'urgency' => 'high',
            ]);

            $clientOptions = config('webpush.client_options', [
                'timeout' => 15,
                'verify'  => false,
            ]);

            $timeout = $clientOptions['timeout'] ?? 15;

            $this->webPush = new WebPush(
                ['VAPID' => $vapid],
                $defaultOptions,
                $timeout,
                $clientOptions
            );

            // Automatically reuse connection
            $this->webPush->setAutomaticPadding(false);
        } catch (\Throwable $e) {
            Log::warning('[WebPushService] Failed to initialize WebPush client: ' . $e->getMessage());
            $this->webPush = null;
        }
    }

    /**
     * Send Web Push notification to all active devices of a given user.
     */
    public function sendToUser(User|int $user, array $payload): int
    {
        $userId = is_numeric($user) ? (int)$user : $user->id;
        $subscriptions = PushSubscription::where('user_id', $userId)->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        return $this->sendToSubscriptions($subscriptions, $payload);
    }

    /**
     * Send Web Push notification to multiple users.
     */
    public function sendToUsers(array|Collection $userIds, array $payload): int
    {
        $ids = collect($userIds)->map(fn ($u) => is_numeric($u) ? (int)$u : $u->id)->unique()->all();

        if (empty($ids)) {
            return 0;
        }

        $subscriptions = PushSubscription::whereIn('user_id', $ids)->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        return $this->sendToSubscriptions($subscriptions, $payload);
    }

    /**
     * Send a Web Push notification for an application model change event.
     * Only eligible users (with module permission, excluding actor) are notified.
     */
    public function sendModelChangePush(
        string $model,
        string $action,
        int $recordId,
        string $actorName,
        ?int $actorId,
        string $permissionKey,
        array $recordData = []
    ): int {
        try {
            // Find all active users except actor
            $query = User::where('is_active', true);
            if ($actorId) {
                $query->where('id', '!=', $actorId);
            }
            $users = $query->with('role')->get();

            // Filter users with permission
            $eligibleUserIds = [];
            foreach ($users as $user) {
                if (!$permissionKey || $user->hasPermission($permissionKey)) {
                    $eligibleUserIds[] = $user->id;
                }
            }

            if (empty($eligibleUserIds)) {
                return 0;
            }

            // Build payload
            $payload = $this->buildModelPayload($model, $action, $recordId, $actorName, $recordData);

            return $this->sendToUsers($eligibleUserIds, $payload);
        } catch (\Throwable $e) {
            Log::warning('[WebPushService] sendModelChangePush failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Build rich, informative Web Push notification payload for a model event.
     */
    public function buildModelPayload(
        string $model,
        string $action,
        int $recordId,
        string $actorName,
        array $recordData
    ): array {
        $actionPast = match (strtolower($action)) {
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            default   => $action,
        };

        // Determine specific details
        $details = [];
        if (!empty($recordData['tracking'])) {
            $details[] = 'Ref: ' . $recordData['tracking'];
        } elseif (!empty($recordData['reception_tracking_number'])) {
            $val = is_array($recordData['reception_tracking_number'])
                ? implode(', ', $recordData['reception_tracking_number'])
                : $recordData['reception_tracking_number'];
            $details[] = 'Ref: ' . $val;
        } elseif (!empty($recordData['production_tracking_numbers'])) {
            $val = is_array($recordData['production_tracking_numbers'])
                ? implode(', ', $recordData['production_tracking_numbers'])
                : $recordData['production_tracking_numbers'];
            $details[] = 'Ref: ' . $val;
        } elseif (!empty($recordData['shipping_tracking_numbers'])) {
            $val = is_array($recordData['shipping_tracking_numbers'])
                ? implode(', ', $recordData['shipping_tracking_numbers'])
                : $recordData['shipping_tracking_numbers'];
            $details[] = 'Ref: ' . $val;
        } elseif (!empty($recordData['tracking_number'])) {
            $details[] = 'Ref: ' . $recordData['tracking_number'];
        }

        if (!empty($recordData['weight']) || !empty($recordData['total_weight'])) {
            $w = $recordData['weight'] ?? $recordData['total_weight'];
            $details[] = number_format((float)$w, 2) . ' kg';
        }

        if (!empty($recordData['price']) || !empty($recordData['total_price'])) {
            $p = $recordData['price'] ?? $recordData['total_price'];
            $details[] = number_format((float)$p, 2) . ' MAD';
        }

        if (!empty($recordData['supplier'])) {
            $details[] = 'Supplier: ' . $recordData['supplier'];
        } elseif (!empty($recordData['supplier']['name'])) {
            $details[] = 'Supplier: ' . $recordData['supplier']['name'];
        }

        if (!empty($recordData['category'])) {
            $details[] = $recordData['category'];
        }

        $detailStr = !empty($details) ? ' (' . implode(' • ', $details) . ')' : '';

        $title = "Jupiter ERP • {$model} {$actionPast}";
        $body  = "{$actorName} {$actionPast} a {$model} record{$detailStr}";

        // Determine front-end navigation URL
        $url = $this->determineTargetUrl($model, $recordId);

        return [
            'title'   => $title,
            'body'    => $body,
            'icon'    => '/images/jupiter-logo.png',
            'badge'   => '/favicon.ico',
            'tag'     => "jupiter-{$model}-{$recordId}",
            'renotify'=> true,
            'data'    => [
                'model'     => $model,
                'action'    => $action,
                'record_id' => $recordId,
                'actor'     => $actorName,
                'url'       => $url,
                'timestamp' => time() * 1000,
            ],
        ];
    }

    /**
     * Map model to application frontend route URL.
     */
    protected function determineTargetUrl(string $model, int $recordId): string
    {
        return match ($model) {
            'Charge'            => "/view_charge/{$recordId}",
            'Production'        => "/view_production/{$recordId}",
            'Shipping'          => "/view_shipping/{$recordId}",
            'Inventory'         => "/edit_inventory/{$recordId}",
            'WarehouseTransfer' => "/edit_warehouse_transfer/{$recordId}",
            'SupplierInvoice'   => "/list_supplier_invoice",
            'ClientInvoice'     => "/list_client_invoice",
            'StorageInvoice'    => "/storage_invoices",
            'OrderTransfer'     => "/order_transfers_page",
            'FortnightActivity' => "/fortnight_management",
            'CostConfiguration' => "/cost_configuration_management",
            default             => "/",
        };
    }

    /**
     * Send payload to a collection of PushSubscription models and purge invalid ones.
     */
    protected function sendToSubscriptions(Collection $subscriptions, array $payload): int
    {
        if (!$this->webPush) {
            $this->initWebPush();
            if (!$this->webPush) {
                Log::warning('[WebPushService] WebPush client not available.');
                return 0;
            }
        }

        $payloadJson = json_encode($payload);
        $queuedCount = 0;

        /** @var PushSubscription $subModel */
        foreach ($subscriptions as $subModel) {
            try {
                $sub = $subModel->toWebPushSubscription();
                $this->webPush->queueNotification($sub, $payloadJson);
                $queuedCount++;
            } catch (\Throwable $e) {
                Log::warning("[WebPushService] Invalid subscription ID {$subModel->id}: " . $e->getMessage());
            }
        }

        if ($queuedCount === 0) {
            return 0;
        }

        $successfulSent = 0;
        $expiredIds = [];

        try {
            foreach ($this->webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();

                if ($report->isSuccess()) {
                    $successfulSent++;
                } else {
                    $reason = $report->getReason();
                    Log::info("[WebPushService] Push failed for endpoint: {$endpoint}. Reason: {$reason}");

                    if ($report->isSubscriptionExpired()) {
                        // Mark for deletion
                        $expired = $subscriptions->firstWhere('endpoint', $endpoint);
                        if ($expired) {
                            $expiredIds[] = $expired->id;
                        }
                    }
                }
            }

            // Clean up expired or unregistered endpoints automatically
            if (!empty($expiredIds)) {
                PushSubscription::whereIn('id', $expiredIds)->delete();
                Log::info('[WebPushService] Purged ' . count($expiredIds) . ' expired push subscriptions.');
            }
        } catch (\Throwable $e) {
            Log::warning('[WebPushService] Flush error: ' . $e->getMessage());
        }

        return $successfulSent;
    }
}
