<?php

namespace App\Observers;

use App\Events\ModelChanged;
use Illuminate\Database\Eloquent\Model;

class ModelChangeObserver
{
    private function broadcast(Model $model, string $action): void
    {
        $userName = 'System';
        $user = auth()->user();
        if ($user) {
            $userName = $user->name;
        }

        $modelName = class_basename($model);

        // Get the record data (with relations if loaded)
        $recordData = $model->toArray();

        try {
            event(new ModelChanged($modelName, $action, $model->id ?? 0, $userName, $recordData));
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
}
