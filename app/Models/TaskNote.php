<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One message in a task's note thread between staff and admin.
 */
class TaskNote extends Model
{
    protected $fillable = ['task_id', 'user_id', 'body'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Written by someone on the admin side (admin or super admin). */
    public function isFromAdmin(): bool
    {
        return (bool) $this->user?->hasAnyRole(['super_admin', 'admin']);
    }
}
