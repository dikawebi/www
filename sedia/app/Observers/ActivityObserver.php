<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityObserver
{
    private const SENSITIVE = ['password', 'remember_token'];

    private function sanitize(array $attributes): array
    {
        return array_diff_key($attributes, array_flip(self::SENSITIVE));
    }

    public function created(Model $model): void
    {
        ActivityLog::record(
            action: 'created',
            subject: $model,
            description: class_basename($model).' #'.$model->getKey().' dibuat',
            properties: $this->sanitize($model->getAttributes()),
        );
    }

    public function updated(Model $model): void
    {
        $changes = $model->getDirty();
        if (empty($changes)) {
            return;
        }

        // Jangan log touch timestamp saja + sanitasi
        $filtered = array_diff_key($changes, array_flip(['updated_at', ...self::SENSITIVE]));
        if (empty($filtered)) {
            return;
        }

        ActivityLog::record(
            action: 'updated',
            subject: $model,
            description: class_basename($model).' #'.$model->getKey().' diperbarui',
            properties: [
                'changes' => $this->sanitize($filtered),
                'original' => $this->sanitize(array_intersect_key($model->getOriginal(), $filtered)),
            ],
        );
    }

    public function deleted(Model $model): void
    {
        ActivityLog::record(
            action: 'deleted',
            subject: $model,
            description: class_basename($model).' #'.$model->getKey().' dihapus',
            properties: $this->sanitize($model->getAttributes()),
        );
    }
}
