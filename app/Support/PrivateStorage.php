<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class PrivateStorage
{
    public static function disk(): string
    {
        return config('filesystems.default') === 'r2' ? 'r2' : 'local';
    }

    public static function response(string $path): Response
    {
        $disk = Storage::disk(self::disk());

        if (! $disk->exists($path) && self::disk() !== 'local') {
            $disk = Storage::disk('local');
        }

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
