<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StorageController extends Controller
{
    public function show(string $path): Response
    {
        $path = trim(str_replace('\\', '/', $path), '/');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $useR2 = config('filesystems.default') === 'r2' || (! app()->environment('testing') && ! empty(config('filesystems.disks.r2.key')));
        $primaryDisk = $useR2 ? Storage::disk('r2') : Storage::disk('public');
        $fallbackDisk = $useR2 ? Storage::disk('public') : Storage::disk('r2');

        try {
            if ($primaryDisk->exists($path)) {
                $cdnUrl = config('filesystems.disks.r2.url');
                if ($useR2 && ! empty($cdnUrl)) {
                    return redirect()->away(rtrim($cdnUrl, '/') . '/' . ltrim($path, '/'), 302);
                }

                return $primaryDisk->response(
                    $path,
                    null,
                    ['Cache-Control' => 'public, max-age=86400']
                );
            }
        } catch (\Throwable $e) {
            // S3/R2 connectivity issue; continue to fallback disk
        }

        try {
            if ($fallbackDisk->exists($path)) {
                return $fallbackDisk->response(
                    $path,
                    null,
                    ['Cache-Control' => 'public, max-age=86400']
                );
            }
        } catch (\Throwable $e) {
            // S3/R2 connectivity issue
        }

        abort(404);
    }
}
