<?php

namespace App\Observers;

use App\Events\ModelChanged;
use Illuminate\Database\Eloquent\Model;

class ModelChangeObserver
{
    private function broadcast(Model $model, string $action): void
    {
        // Skip broadcasting for AI assistant requests to avoid noisy notifications
        if ($this->isAiAssistantRequest()) {
            return;
        }

        $userName = 'System';
        $userId = null;
        $user = auth()->user();
        if ($user) {
            $userName = $user->name;
            $userId = $user->id;
        }

        $modelName = class_basename($model);

        // Get the record data (with relations if loaded)
        $recordData = $model->toArray();

        try {
            broadcast(new ModelChanged($modelName, $action, $model->id ?? 0, $userName, $recordData, $userId))
                ->toOthers();
        } catch (\Throwable $e) {
            // Don't break the main operation if broadcasting fails
            report($e);
        }
    }

    public function created(Model $model): void
    {
        $this->broadcast($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->broadcast($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->broadcast($model, 'deleted');
    }

    /**
     * Check if the current request is an AI assistant API call.
     */
    private function isAiAssistantRequest(): bool
    {
        $request = request();
        if (!$request) {
            return false;
        }

        return $request->is('api/ai-assistant', 'api/ai-assistant/*');
    }
}
