<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Slug
{
    /**
     * A URL-safe slug that is unique in the model's table.
     * Bangla-only names become "item-<random>" because Str::slug drops non-Latin letters.
     */
    public static function unique(string $modelClass, ?string $wanted, string $fallbackFrom, ?int $ignoreId = null): string
    {
        $base = Str::slug($wanted ?: $fallbackFrom) ?: 'item-' . Str::lower(Str::random(6));
        $slug = $base;
        $i = 2;

        /** @var Model $model */
        $model = new $modelClass();
        while ($model->newQuery()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
