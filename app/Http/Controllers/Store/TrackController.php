<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Support\VisitorInfo;
use Illuminate\Http\Request;

/**
 * Storefront page views for Analytics. The browser sends one "view" per page and later
 * a "ping" with Core Web Vitals and a heartbeat (for live visitors and time on page).
 * Ids are random strings made in the browser; no IP address or personal data is stored.
 */
class TrackController extends Controller
{
    public const PAGE_TYPES = ['home', 'product', 'category', 'brand', 'search', 'deals', 'shop', 'page', 'cart', 'checkout', 'order', 'account', 'other'];

    public function view(Request $request)
    {
        $ua = $request->userAgent();
        if (VisitorInfo::isBot($ua) || $request->user('sanctum')?->isStaff()) {
            return response()->json(['id' => null]);
        }

        $data = $request->validate([
            'visitor_id' => ['required', 'string', 'regex:/^[a-z0-9]{16,32}$/i'],
            'session_id' => ['required', 'string', 'regex:/^[a-z0-9]{16,32}$/i'],
            'path'       => ['required', 'string', 'max:500'],
            'page_type'  => ['nullable', 'in:' . implode(',', self::PAGE_TYPES)],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'referrer'   => ['nullable', 'string', 'max:500'],
            'utm_source' => ['nullable', 'string', 'max:60'],
            'utm_medium' => ['nullable', 'string', 'max:60'],
        ]);

        [$source, $sourceName] = VisitorInfo::source($data['referrer'] ?? null, $data['utm_source'] ?? null, $data['utm_medium'] ?? null, $request->getHost());

        // Product pages: find the product from its address
        if (empty($data['product_id']) && preg_match('#^/product/([a-z0-9-]+)#', $data['path'], $m)) {
            $data['product_id'] = \App\Models\Product::where('slug', $m[1])->value('id');
        }

        // Later pages of a visit came from the same place as its first page
        if ($source === 'direct') {
            $first = Visit::where('session_id', $data['session_id'])->oldest('id')->first(['source', 'source_name']);
            if ($first) {
                [$source, $sourceName] = [$first->source, $first->source_name];
            }
        }

        $visit = Visit::create([
            'visitor_id'   => $data['visitor_id'],
            'session_id'   => $data['session_id'],
            'user_id'      => $request->user('sanctum')?->id,
            'path'         => mb_substr($data['path'], 0, 500),
            'page_type'    => $data['page_type'] ?? 'other',
            'product_id'   => $data['product_id'] ?? null,
            'referrer'     => isset($data['referrer']) ? mb_substr($data['referrer'], 0, 500) : null,
            'source'       => $source,
            'source_name'  => $sourceName,
            'device'       => VisitorInfo::device($ua),
            'browser'      => VisitorInfo::browser($ua),
            'os'           => VisitorInfo::os($ua),
            'last_seen_at' => now(),
        ]);

        return response()->json(['id' => $visit->id]);
    }

    /** Heartbeat and Web Vitals for a page view (sent with navigator.sendBeacon). */
    public function ping(Request $request, Visit $visit)
    {
        $data = json_decode($request->getContent(), true) ?: $request->all();
        if (($data['visitor_id'] ?? null) !== $visit->visitor_id || $visit->created_at->lt(now()->subHours(6))) {
            return response()->noContent();
        }

        $vitals = [];
        foreach (['lcp' => 60000, 'inp' => 60000, 'ttfb' => 60000] as $k => $max) {
            if (isset($data[$k]) && is_numeric($data[$k]) && $data[$k] >= 0) {
                $vitals[$k] = (int) min(round($data[$k]), $max);
            }
        }
        if (isset($data['cls']) && is_numeric($data['cls']) && $data['cls'] >= 0) {
            $vitals['cls'] = round(min((float) $data['cls'], 10), 3);
        }

        $visit->forceFill($vitals + ['last_seen_at' => now()])->save();

        return response()->noContent();
    }

    /** The traffic source to credit an order to: the visitor's last non-direct visit in 30 days. */
    public static function sourceFor(?string $visitorId): ?string
    {
        if (! $visitorId) {
            return null;
        }
        $visits = Visit::where('visitor_id', $visitorId)->where('created_at', '>=', now()->subDays(30));
        if (! (clone $visits)->exists()) {
            return null;
        }

        return (clone $visits)->where('source', '!=', 'direct')->latest('id')->value('source') ?? 'direct';
    }
}
