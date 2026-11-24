<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Move uploaded files to the disk that matches who may see them:
 *
 *  - guest ID documents were on the public disk (reachable at /media/... with
 *    no login); they move to the private disk;
 *  - the site logo and favicon were saved to the private disk by mistake, so
 *    /media could never show them; they move to the public disk.
 *
 * Safe to run more than once: files already in place are left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $public = Storage::disk('public');
        $privateDisk = config('filesystems.default') === 'r2' ? 'r2' : 'local';
        $private = Storage::disk($privateDisk);

        foreach (DB::table('users')->whereNotNull('id_document')->pluck('id_document') as $path) {
            if ($public->exists($path)) {
                if (! $private->exists($path)) {
                    $private->writeStream($path, $public->readStream($path));
                }
                $public->delete($path);
            }
        }

        if (! DB::getSchemaBuilder()->hasTable('settings')) {
            return;
        }
        foreach (['logo', 'favicon'] as $key) {
            $path = DB::table('settings')->where('key', $key)->value('value');
            if (! $path) {
                continue;
            }
            $stranded = 'public/'.ltrim($path, '/');
            if (! $public->exists($path) && $private->exists($stranded)) {
                $public->writeStream($path, $private->readStream($stranded));
                $private->delete($stranded);
            }
        }
    }

    public function down(): void
    {
        // Not reversed: putting ID documents back on a public disk would re-expose them.
    }
};
