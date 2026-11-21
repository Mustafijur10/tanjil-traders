<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\Period;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Google Search Console: clicks, impressions, average position and the words people searched
 * on Google before visiting. Uses a service account (Settings › Integrations & SEO), signs its own
 * token with openssl, and caches answers for six hours (Google's data is about two days behind anyway).
 */
class SearchConsole
{
    public function configured(): bool
    {
        return filled(Setting::get('integrations.search_console_site')) && filled(Setting::get('integrations.search_console_json'));
    }

    public function report(Period $p): array
    {
        if (! $this->configured()) {
            return ['configured' => false];
        }

        $site = Setting::get('integrations.search_console_site');
        $from = $p->from->format('Y-m-d');
        $to = $p->to->format('Y-m-d');

        try {
            return Cache::remember("gsc:{$site}:{$from}:{$to}", 6 * 3600, function () use ($site, $from, $to) {
                $query = fn (array $body) => $this->query($site, ['startDate' => $from, 'endDate' => $to] + $body);
                $totals = $query([])[0] ?? [];
                $row = fn ($r) => [
                    'key'         => $r['keys'][0] ?? null,
                    'clicks'      => (int) ($r['clicks'] ?? 0),
                    'impressions' => (int) ($r['impressions'] ?? 0),
                    'ctr'         => round(($r['ctr'] ?? 0) * 100, 2),
                    'position'    => round($r['position'] ?? 0, 1),
                ];

                return [
                    'configured' => true,
                    'site'       => $site,
                    'totals'     => $row($totals),
                    'series'     => array_map($row, $query(['dimensions' => ['date'], 'rowLimit' => 500])),
                    'queries'    => array_map($row, $query(['dimensions' => ['query'], 'rowLimit' => 20])),
                    'pages'      => array_map($row, $query(['dimensions' => ['page'], 'rowLimit' => 10])),
                ];
            });
        } catch (Throwable $e) {
            return ['configured' => true, 'error' => $e->getMessage()];
        }
    }

    private function query(string $site, array $body): array
    {
        $res = Http::withToken($this->token())->timeout(30)
            ->post('https://searchconsole.googleapis.com/webmasters/v3/sites/' . rawurlencode($site) . '/searchAnalytics/query', $body);
        if (! $res->ok()) {
            throw new \RuntimeException('Search Console: ' . ($res->json('error.message') ?? 'request failed (' . $res->status() . ')'));
        }

        return $res->json('rows', []);
    }

    /** OAuth token from the service account key (JWT bearer grant). */
    private function token(): string
    {
        $key = json_decode((string) Setting::get('integrations.search_console_json'), true);
        if (empty($key['client_email']) || empty($key['private_key'])) {
            throw new \RuntimeException('The service account JSON is not valid. Paste the whole key file.');
        }

        return Cache::remember('gsc-token:' . md5($key['client_email']), 3300, function () use ($key) {
            $b64 = fn ($v) => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
            $now = time();
            $head = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $b64(json_encode([
                'iss'   => $key['client_email'],
                'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));
            if (! openssl_sign("{$head}.{$claims}", $signature, $key['private_key'], 'sha256WithRSAEncryption')) {
                throw new \RuntimeException('Could not sign with the service account key.');
            }

            $res = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => "{$head}.{$claims}." . $b64($signature),
            ]);
            if (! $res->ok()) {
                throw new \RuntimeException('Google refused the service account: ' . ($res->json('error_description') ?? $res->status()));
            }

            return $res->json('access_token');
        });
    }
}
