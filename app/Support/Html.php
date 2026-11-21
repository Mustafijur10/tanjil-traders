<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Cleans rich text from the admin editor before it is saved, so product descriptions and
 * pages can never carry scripts or unsafe markup onto the website.
 */
class Html
{
    private static ?HTMLPurifier $purifier = null;

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim(strip_tags($html, '<img><table>')) === '') {
            return null;
        }

        return trim(self::purifier()->purify($html)) ?: null;
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));
        $config->set('HTML.Allowed', implode(',', [
            'h2', 'h3', 'h4', 'p[style]', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'blockquote',
            'ul', 'ol', 'li', 'a[href|title|rel|target]', 'img[src|alt|width|height]',
            'table', 'thead', 'tbody', 'tr', 'th[colspan|rowspan]', 'td[colspan|rowspan]', 'hr', 'code', 'pre',
        ]));
        $config->set('CSS.AllowedProperties', ['text-align']);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.TargetNoopener', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $config->set('AutoFormat.RemoveEmpty', true);

        return self::$purifier = new HTMLPurifier($config);
    }
}
