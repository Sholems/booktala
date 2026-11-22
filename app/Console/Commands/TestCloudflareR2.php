<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestCloudflareR2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'r2:test {--keep : Do not delete the test file after upload}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Cloudflare R2 bucket connection, read/write permissions, and public CDN URL';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== BookTala Cloudflare R2 Storage Diagnostic ===');

        $disk = config('filesystems.default');
        $bucket = config('filesystems.disks.r2.bucket');
        $endpoint = config('filesystems.disks.r2.endpoint');
        $key = config('filesystems.disks.r2.key');
        $cdnUrl = config('filesystems.disks.r2.url');

        $this->line("Default Filesystem Disk: <comment>{$disk}</comment>");
        $this->line("R2 Bucket: <comment>{$bucket}</comment>");
        $this->line("R2 Endpoint: <comment>{$endpoint}</comment>");
        $this->line("R2 Key ID: <comment>" . ($key ? substr($key, 0, 6) . '...' . substr($key, -4) : 'NOT SET') . "</comment>");
        $this->line("Public CDN URL: <comment>" . ($cdnUrl ?: 'None (streaming via /media/{path})') . "</comment>");

        if (empty($key) || empty($bucket) || empty($endpoint)) {
            $this->error('Cloudflare R2 is missing required configuration in .env!');
            $this->line('Ensure CLOUDFLARE_R2_ACCESS_KEY_ID, CLOUDFLARE_R2_SECRET_ACCESS_KEY, and CLOUDFLARE_R2_ENDPOINT are set.');
            return Command::FAILURE;
        }

        $testPath = 'diagnostics/test-' . time() . '.txt';
        $testContent = "BookTala Cloudflare R2 Storage Verification\nTimestamp: " . now()->toIso8601String() . "\nStatus: OK";

        $this->line("\n[1/3] Uploading test object to [{$testPath}] on R2...");

        try {
            $r2 = Storage::disk('r2');
            $uploadSuccess = $r2->put($testPath, $testContent);

            if (!$uploadSuccess) {
                $this->error('Failed to put test object onto Cloudflare R2.');
                return Command::FAILURE;
            }
            $this->info('Upload successful!');

            $this->line("\n[2/3] Verifying read & existence from Cloudflare R2...");
            if (!$r2->exists($testPath)) {
                $this->error('File was uploaded but exists() check failed.');
                return Command::FAILURE;
            }

            $retrieved = $r2->get($testPath);
            if ($retrieved !== $testContent) {
                $this->error('Retrieved file content does not match original payload.');
                return Command::FAILURE;
            }
            $this->info('Read verified! File content matches 100%.');

            if (!empty($cdnUrl)) {
                $publicUrl = rtrim($cdnUrl, '/') . '/' . $testPath;
                $this->line("Public Object URL: <info>{$publicUrl}</info>");
            }

            if ($this->option('keep')) {
                $this->info("\nPreserved test file at [{$testPath}].");
            } else {
                $this->line("\n[3/3] Cleaning up test object...");
                $r2->delete($testPath);
                $this->info('Cleanup complete.');
            }

            $this->info("\nCloudflare R2 storage is active, healthy, and operational!");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Cloudflare R2 Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
