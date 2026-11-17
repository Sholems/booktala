<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    protected $fillable = [
        'section_key',
        'title',
        'subtitle',
        'content',
        'image',
        'button_text',
        'button_link',
        'order',
        'is_visible',
        'extra_data',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'extra_data' => 'array',
        ];
    }
}
