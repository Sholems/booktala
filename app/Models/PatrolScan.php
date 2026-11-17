<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrolScan extends Model
{
    protected $fillable = ['patrol_checkpoint_id', 'staff_id', 'scanned_at', 'lat', 'lng', 'note'];

    protected function casts(): array
    {
        return ['scanned_at' => 'datetime'];
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(PatrolCheckpoint::class, 'patrol_checkpoint_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
