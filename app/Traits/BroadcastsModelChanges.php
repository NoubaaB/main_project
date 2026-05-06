<?php

namespace App\Traits;

use App\Events\ModelChanged;
use Illuminate\Support\Facades\Auth;

/**
 * Broadcasts a ModelChanged event for real-time sync across clients.
 */
trait BroadcastsModelChanges
{
    /**
     * Broadcast a model change.
     *
     * @param string $model   e.g. 'ChargeCategory'
     * @param string $action  e.g. 'created', 'updated', 'deleted'
     * @param int    $recordId
     * @param array  $recordData  The record's current data (empty on delete)
     */
    protected function broadcastChange(string $model, string $action, int $recordId, array $recordData = []): void
    {
        try {
            $userName = Auth::user()?->name ?? 'System';
            broadcast(new ModelChanged($model, $action, $recordId, $userName, $recordData))->toOthers();
        } catch (\Throwable $e) {
            // Silently fail if broadcasting is not configured
            \Log::warning('[BroadcastsModelChanges] Failed: ' . $e->getMessage());
        }
    }
}
