<?php

namespace App\Support;

/**
 * Works out device, browser, OS and traffic source from the user agent and referrer.
 * Deliberately simple: good enough for shop analytics, no external package, no IP stored.
 */
class VisitorInfo
{
    private const SEARCH = ['google', 'bing', 'yahoo', 'duckduckgo', 'yandex', 'baidu', 'ecosia'];
    private const SOCIAL = [
        'facebook' => 'facebook', 'fb.' => 'facebook', 'm.me' => 'messenger', 'messenger' => 'messenger', 'instagram' => 'instagram',
        'youtube' => 'youtube', 'youtu.be' => 'youtube', 'tiktok' => 'tiktok', 't.co' => 'twitter', 'twitter' => 'twitter', 'x.com' => 'twitter',
        'linkedin' => 'linkedin', 'whatsapp' => 'whatsapp', 'pinterest' => 'pinterest', 'reddit' => 'reddit',
    ];

    public static function isBot(?string $ua): bool
    {
        return ! $ua || (bool) preg_match('/bot|crawl|spider|slurp|headless|lighthouse|preview|facebookexternalhit|curl|wget|python|axios/i', $ua);
    }

    public static function device(?string $ua): string
    {
        return match (true) {
            (bool) preg_match('/ipad|tablet|kindle|silk|(android(?!.*mobile))/i', (string) $ua) => 'tablet',
            (bool) preg_match('/mobi|iphone|ipod|android|opera mini/i', (string) $ua)            => 'mobile',
            default                                                                             => 'desktop',
        };
    }

    public static function browser(?string $ua): string
    {
        $ua = (string) $ua;

        return match (true) {
            str_contains($ua, 'Edg/')            => 'Edge',
            str_contains($ua, 'OPR/'), str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'SamsungBrowser')  => 'Samsung Internet',
            str_contains($ua, 'UCBrowser')       => 'UC Browser',
            str_contains($ua, 'Firefox/')        => 'Firefox',
            str_contains($ua, 'Chrome/')         => 'Chrome',
            str_contains($ua, 'Safari/')         => 'Safari',
            default                              => 'Other',
        };
    }

    public static function os(?string $ua): string
    {
        $ua = (string) $ua;

        return match (true) {
            (bool) preg_match('/iphone|ipad|ipod/i', $ua) => 'iOS',
            str_contains($ua, 'Android')                 => 'Android',
            str_contains($ua, 'Windows')                 => 'Windows',
            str_contains($ua, 'Mac OS')                  => 'macOS',
            str_contains($ua, 'Linux')                   => 'Linux',
            default                                      => 'Other',
        };
    }

    /**
     * [source, source_name] from UTM tags or the referrer.
     * source: direct | search | social | referral | email | ads
     */
    public static function source(?string $referrer, ?string $utmSource, ?string $utmMedium, string $ownHost): array
    {
        $medium = strtolower((string) $utmMedium);
        $name = $utmSource ? mb_substr(strtolower($utmSource), 0, 40) : null;

        if (in_array($medium, ['cpc', 'ppc', 'paid', 'paidsocial', 'paid_social', 'ads', 'ad'], true)) {
            return ['ads', $name];
        }
        if ($medium === 'email' || $medium === 'sms') {
            return ['email', $name ?? $medium];
        }
        if (in_array($medium, ['social', 'social-media'], true)) {
            return ['social', $name];
        }

        $host = strtolower((string) parse_url((string) $referrer, PHP_URL_HOST));
        if (! $host || $host === strtolower($ownHost)) {
            return $name ? ['referral', $name] : ['direct', null];
        }
        foreach (self::SEARCH as $engine) {
            if (str_contains($host, $engine)) {
                return ['search', $engine];
            }
        }
        foreach (self::SOCIAL as $needle => $network) {
            if (str_contains($host, $needle)) {
                return ['social', $network];
            }
        }

        return ['referral', mb_substr(preg_replace('/^www\./', '', $host), 0, 40)];
    }
}
