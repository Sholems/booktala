<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guest identity documents. They are personal data, so they live on the
 * private disk and are only ever streamed through an authorised route,
 * never served from a public URL.
 */
class IdDocuments
{
    public const DISK = 'local';

    public const DIRECTORY = 'id-documents';

    public static function store(UploadedFile $file): string
    {
        return $file->store(self::DIRECTORY, self::DISK);
    }

    public static function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
        // Files uploaded before documents moved to private storage.
        Storage::disk('public')->delete($path);
    }

    public static function response(?string $path): Response
    {
        abort_unless($path && Storage::disk(self::DISK)->exists($path), 404);

        return Storage::disk(self::DISK)->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
