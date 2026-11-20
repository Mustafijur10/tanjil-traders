<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * English sentence -> Bangla sentence. The English text is the key.
 * status: missing (no Bangla yet), auto (machine translated, needs checking), reviewed (checked by a person).
 */
class Translation extends Model
{
    public const STATUSES = ['missing', 'auto', 'reviewed'];

    protected $fillable = ['hash', 'source', 'bn', 'status', 'group'];

    private const CACHE_KEY = 'translations.bn';

    public static function hashOf(string $text): string
    {
        return md5(trim($text));
    }

    /** Add an English text to the list if it is not there yet. */
    public static function register(?string $text, string $group = 'ui'): void
    {
        $text = trim((string) $text);
        if ($text === '' || mb_strlen($text) > 2000) {
            return;
        }

        static::query()->firstOrCreate(
            ['hash' => static::hashOf($text)],
            ['source' => $text, 'group' => $group, 'status' => 'missing'],
        );
    }

    /** English => Bangla for every text that has a Bangla version. Sent to the browser once. */
    public static function dictionary(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->whereNotNull('bn')->where('bn', '!=', '')
            ->pluck('bn', 'source')
            ->all());
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flush());
        static::deleted(fn () => static::flush());
    }
}
