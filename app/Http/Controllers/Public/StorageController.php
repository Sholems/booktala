<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StorageController extends Controller
{
    /** @var list<string> */
    private const PUBLIC_PREFIXES = [
        'avatars/',
        'blogs/',
        'property-images/',
        'settings/',
    ];

    public function show(string $path): Response
    {
        $path = trim(str_replace('\\', '/', $path), '/');

        if ($path === '' || str_contains($path, '..') || ! $this->isPublicPath($path)) {
            abort(404);
        }

        $disk = Storage::disk('public');

        try {
            if ($disk->exists($path)) {
                $cdnUrl = config('filesystems.disks.public.url');
                if (config('filesystems.disks.public.driver') === 's3' && ! empty($cdnUrl)) {
                    return redirect()->away(rtrim($cdnUrl, '/').'/'.ltrim($path, '/'), 302);
                }

                return $disk->response(
                    $path,
                    null,
                    ['Cache-Control' => 'public, max-age=86400']
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }

        abort(404);
    }

    private function isPublicPath(string $path): bool
    {
        foreach (self::PUBLIC_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
