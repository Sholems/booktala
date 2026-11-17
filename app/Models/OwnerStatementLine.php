<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerStatementLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['owner_statement_id', 'property_id', 'type', 'source_id', 'date', 'description', 'nights', 'amount'];

    protected function casts(): array
    {
        return ['date' => 'date', 'amount' => 'decimal:2'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
