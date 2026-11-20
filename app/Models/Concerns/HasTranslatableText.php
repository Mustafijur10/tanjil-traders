<?php

namespace App\Models\Concerns;

use App\Models\Translation;

/**
 * Adds the model's `$translatable` fields to the translations list whenever they change,
 * so admins can give each English text a Bangla version in Settings -> Language.
 */
trait HasTranslatableText
{
    public static function bootHasTranslatableText(): void
    {
        static::saved(function ($model) {
            foreach ($model->translatable ?? [] as $field) {
                if ($model->wasChanged($field) || $model->wasRecentlyCreated) {
                    Translation::register($model->{$field}, 'content');
                }
            }
        });
    }
}
