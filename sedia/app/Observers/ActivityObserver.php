<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityObserver
{
    public function created(Model $model): void
    {
        ActivityLog::record(
            action: 'created',
            subject: $model,
            description: class_basename($model).' #'.$model->getKey().' dibuat',
            properties: $model->getAttributes(),
        );
    }

    public function updated(Model $model): void
    {
        $changes = $model->getDirty();
        if (empty($changes)) {
            return;
        }

        // Jangan log touch timestamp saja
        $filtered = array_diff_key($changes, array_flip(['updated_at']));
        if (empty($filtered)) {
            return;
        }

        ActivityLog::record(
            action: 'updated',
            subject: $model,
            description: class_basename($model).' #'.$model->getKey().' diperbarui',
            properties: [
                'changes' => $filtered,
                'original' => array_intersect_key($model->getOriginal(), $filtered),
            ],
        );
    }

    public function deleted(Model $model): void
    {
        ActivityLog::record(
            action: 'deleted',
            subject: $model,
            description: class_basename($model).' #'.$model->getKey().' dihapus',
            properties: $model->getAttributes(),
        );
    }
}
