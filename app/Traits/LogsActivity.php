<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            self::logActivity('create', $model);
        });

        static::updated(function ($model) {
            self::logActivity('update', $model, $model->getOriginal());
        });

        static::deleted(function ($model) {
            self::logActivity('delete', $model);
        });
    }

    protected static function logActivity($action, $model, $oldValues = [])
    {
        $user = auth()->user();

        if (! $user) {
            $userId = null;
        } else {
            $userId = $user->id;
        }

        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $action === 'update' ? $oldValues : null,
            'new_values' => $action !== 'delete' ? $model->toArray() : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
