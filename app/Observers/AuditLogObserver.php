<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogObserver
{
    public function created(Model $model): void
    {
        $this->logAction($model, 'CREATE', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $dirty = $model->getDirty();
        $old = [];
        $new = [];

        foreach ($dirty as $key => $value) {
            $old[$key] = $model->getOriginal($key);
            $new[$key] = $value;
        }

        // Only log if something actually changed (excluding updated_at)
        unset($old['updated_at'], $new['updated_at']);
        if (count($new) > 0) {
            $this->logAction($model, 'UPDATE', $old, $new);
        }
    }

    public function deleted(Model $model): void
    {
        $this->logAction($model, 'DELETE', $model->getAttributes(), null);
    }

    protected function logAction(Model $model, string $action, ?array $oldValues, ?array $newValues): void
    {
        // Prevent infinite logging recursion
        if ($model instanceof AuditLog) {
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => (string) $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
