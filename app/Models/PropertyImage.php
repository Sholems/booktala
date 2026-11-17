<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PropertyImage extends Model
{
    protected $fillable = ['property_id', 'image_path', 'is_primary', 'sort_order'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function getImagePathAttribute(?string $value): ?string
    {
        if (!$value) {
            return $value;
        }

        $value = trim($value);

        // Already an absolute URL (seed/demo or external image).
        if (Str::startsWith($value, ['http://', 'https://', '//', 'data:'])) {
            return $value;
        }

        // Support values already saved as URL path prefixes.
        if (Str::startsWith($value, '/storage/')) {
            $value = Str::after($value, '/storage/');
        } elseif (Str::startsWith($value, 'storage/')) {
            $value = Str::after($value, 'storage/');
        } elseif (Str::startsWith($value, '/media/')) {
            $value = Str::after($value, '/media/');
        } elseif (Str::startsWith($value, 'media/')) {
            $value = Str::after($value, 'media/');
        }

        // Support paths saved as "public/..." from older code.
        if (Str::startsWith($value, 'public/')) {
            $value = Str::after($value, 'public/');
        }

        // Serve through application route to avoid cPanel symlink/static path issues.
        return url('media/' . ltrim($value, '/'));
    }
}
