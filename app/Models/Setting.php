<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Site settings (name, contact details, branding, credentials).
 *
 * Every page layout reads several settings, so all of them are loaded once,
 * kept in the cache and in memory for the request, and the cache is cleared
 * whenever a setting changes.
 */
class Setting extends Model
{
    private const CACHE_KEY = 'settings.all';

    /** Container key for this request's copy (reset per request and per test). */
    private const INSTANCE = 'settings.loaded';

    protected $fillable = ['key', 'value', 'group'];

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    public static function get($key, $default = null)
    {
        if (! app()->bound(self::INSTANCE)) {
            app()->instance(self::INSTANCE, Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all()));
        }
        $all = app(self::INSTANCE);

        return isset($all[$key]) ? $all[$key] : $default;
    }

    public static function set($key, $value, $group = null)
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }

    public static function flushCache(): void
    {
        app()->forgetInstance(self::INSTANCE);
        Cache::forget(self::CACHE_KEY);
    }
}
