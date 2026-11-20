<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Store-wide settings, stored as key => JSON value and cached as one array.
 * Setting::get('store.name'), Setting::put(['store.name' => 'Tanjil Traders']).
 */
class Setting extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $primaryKey = 'key';
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'settings.all';

    /** Keys that may be sent to the storefront. Everything else (API keys ...) stays private. */
    public const PUBLIC_PREFIXES = ['store.', 'appearance.', 'language.', 'social.', 'checkout.', 'seo.'];

    /** Defaults from config/store.php, overridden by saved values. */
    public static function cached(): array
    {
        $saved = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->pluck('value', 'key')
            ->map(fn ($v) => json_decode($v, true))
            ->all());

        return array_merge(config('store.settings', []), $saved);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::cached()[$key] ?? $default;
    }

    /** Saves only keys that exist in config/store.php. */
    public static function put(array $values): void
    {
        $known = config('store.settings', []);
        foreach ($values as $key => $value) {
            // '__set__' is the masked secret coming back unchanged from the admin form.
            if (array_key_exists($key, $known) && $value !== '__set__') {
                static::query()->updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
            }
        }
        Cache::forget(self::CACHE_KEY);
    }

    public static function publicValues(): array
    {
        return collect(static::cached())
            ->filter(fn ($v, $k) => collect(self::PUBLIC_PREFIXES)->contains(fn ($p) => str_starts_with($k, $p)))
            ->all();
    }

    /** All values for the admin, with secrets replaced by a "set / not set" marker. */
    public static function adminValues(): array
    {
        $secrets = config('store.secret_settings', []);

        return collect(static::cached())
            ->map(fn ($v, $k) => in_array($k, $secrets, true) ? ($v ? '__set__' : '') : $v)
            ->all();
    }
}
