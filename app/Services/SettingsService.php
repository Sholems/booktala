<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    private ?array $settings = null;

    public function get(string $key, $default = null)
    {
        if ($this->settings === null) {
            $this->loadSettings();
        }

        return $this->settings[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        DB::table('system_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'type' => is_bool($value) ? 'boolean' : 'text']
        );

        Cache::forget('system_settings');
        $this->settings = null;
    }

    public function all(): array
    {
        if ($this->settings === null) {
            $this->loadSettings();
        }

        return $this->settings;
    }

    private function loadSettings(): void
    {
        if (! Schema::hasTable('system_settings')) {
            $this->settings = [];

            return;
        }

        try {
            $this->settings = Cache::remember('system_settings', 3600, function () {
                return DB::table('system_settings')->pluck('value', 'key')->toArray();
            });
        } catch (\Throwable $e) {
            $this->settings = [];
        }
    }

    public function clearCache(): void
    {
        Cache::forget('system_settings');
        $this->settings = null;
    }
}
