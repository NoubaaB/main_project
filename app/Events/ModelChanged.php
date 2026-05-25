<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModelChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $model;
    public string $action;
    public int $recordId;
    public string $userName;
    public string $timestamp;
    public array $recordData;
    public ?int $actorId;
    public string $permissionKey;

    public function __construct(
        string $model,
        string $action,
        int $recordId,
        string $userName,
        array $recordData = [],
        ?int $actorId = null,
        string $permissionKey = ''
    ) {
        $this->model = $model;
        $this->action = $action;
        $this->recordId = $recordId;
        $this->userName = $userName;
        $this->timestamp = now()->toIso8601String();
        $this->recordData = $recordData;
        $this->actorId = $actorId;
        $this->permissionKey = $permissionKey;
    }

    public function broadcastOn(): array
    {
        return [new Channel('model-changes')];
    }

    public function broadcastAs(): string
    {
        return 'model.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'model' => $this->model,
            'action' => $this->action,
            'record_id' => $this->recordId,
            'user_name' => $this->userName,
            'timestamp' => $this->timestamp,
            'record_data' => $this->recordData,
            'actor_id' => $this->actorId,
            'permission_key' => $this->permissionKey,
        ];
    }
}
