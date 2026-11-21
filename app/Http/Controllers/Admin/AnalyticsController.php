<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\SearchLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Services\SearchConsole;
use App\Support\Period;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Analytics reports. Every number comes from the shop's own data: orders, order items,
 * stock, visits (page views tracked on the storefront), searches and wishlists.
 * "Sales" means orders that were not cancelled or returned.
 */
class AnalyticsController extends Controller
{
    private const LOST = ['cancelled', 'returned'];

    // ───────────────────────── Sales ─────────────────────────
    public function sales(Request $request)
    {
        $p = Period::fromRequest($request);
        $now = $this->salesTotals($p->range());
        $before = $this->salesTotals($p->previous());

        $orders = fn () => Order::query()->whereBetween('orders.created_at', $p->range());
        $sold = fn () => $orders()->whereNotIn('orders.status', self::LOST);

        $series = $sold()->selectRaw($p->bucketSql('orders.created_at') . ' as k, sum(total) as revenue, count(*) as orders')
            ->groupBy('k')->get()->keyBy('k');
        $profitSeries = $this->itemsQuery($p->range())
            ->selectRaw($p->bucketSql('orders.created_at') . ' as k, sum((order_items.price - order_items.cost_price) * order_items.qty) as profit')
            ->whereNotNull('order_items.cost_price')->groupBy('k')->pluck('profit', 'k');

        return response()->json([
            'period'  => $this->periodInfo($p),
            'totals'  => collect($now)->map(fn ($v, $k) => ['value' => $v, 'delta' => Period::delta($v, $before[$k] ?? 0)]),
            'series'  => collect($p->keys())->map(fn ($k) => [
                'key'     => $k,
                'revenue' => (float) ($series[$k]->revenue ?? 0),
                'orders'  => (int) ($series[$k]->orders ?? 0),
                'profit'  => round((float) ($profitSeries[$k] ?? 0), 2),
            ]),
            'by_payment' => $sold()->selectRaw('payment_method as name, count(*) as orders, sum(total) as revenue')->groupBy('payment_method')->orderByDesc('revenue')->get(),
            'by_status'  => $orders()->selectRaw('status as name, count(*) as orders, sum(total) as revenue')->groupBy('status')->get(),
            'by_city'    => $sold()->selectRaw('city as name, count(*) as orders, sum(total) as revenue')->groupBy('city')->orderByDesc('revenue')->limit(8)->get(),
            'by_channel' => $sold()->selectRaw('source as name, count(*) as orders, sum(total) as revenue')->groupBy('source')->get(),
            // GROUP BY uses the expression: on MySQL a "name" alias would pick the orders.name column
            'by_traffic' => $sold()->selectRaw("coalesce(traffic_source, 'unknown') as name, count(*) as orders, sum(total) as revenue")->groupByRaw("coalesce(traffic_source, 'unknown')")->orderByDesc('revenue')->get(),
            'by_category' => $this->top($this->itemsQuery($p->range())
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->join('categories as c', 'c.id', '=', 'products.category_id')
                ->leftJoin('categories as parent', 'parent.id', '=', 'c.parent_id')
                ->selectRaw('coalesce(parent.name, c.name) as name, sum(order_items.total) as revenue, sum(order_items.qty) as units')
                ->groupByRaw('coalesce(parent.name, c.name)')->orderByDesc('revenue')->get(), 'revenue', ['units']),
        ]);
    }

    private function salesTotals(array $range): array
    {
        $sold = Order::whereBetween('created_at', $range)->whereNotIn('status', self::LOST);
        $all = Order::whereBetween('created_at', $range)->count();
        $items = $this->itemsQuery($range);

        $revenue = (float) (clone $sold)->sum('total');
        $count = (clone $sold)->count();

        return [
            'revenue'     => $revenue,
            'orders'      => $count,
            'aov'         => $count ? round($revenue / $count, 2) : 0,
            'units'       => (int) (clone $items)->sum('order_items.qty'),
            'profit'      => round((float) (clone $items)->whereNotNull('order_items.cost_price')->selectRaw('sum((order_items.price - order_items.cost_price) * order_items.qty) as p')->value('p') - (float) (clone $sold)->sum('discount'), 2),
            'discount'    => (float) (clone $sold)->sum('discount'),
            'shipping'    => (float) (clone $sold)->sum('shipping_cost'),
            'refunds'     => (float) DB::table('order_returns')->where('status', 'refunded')->whereBetween('updated_at', $range)->sum('refund_amount'),
            'cancel_rate' => $all ? round(Order::whereBetween('created_at', $range)->where('status', 'cancelled')->count() / $all * 100, 1) : 0,
        ];
    }

    /** Order lines of sold orders in a date range. */
    private function itemsQuery(array $range): QueryBuilder
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', $range)
            ->whereNotIn('orders.status', self::LOST);
    }

    // ───────────────────────── Products ─────────────────────────
    public function products(Request $request)
    {
        $p = Period::fromRequest($request);

        $top = $this->itemsQuery($p->range())
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->selectRaw('order_items.product_id as id, max(order_items.name) as name, max(products.slug) as slug, sum(order_items.qty) as units,
                sum(order_items.total) as revenue, count(distinct order_items.order_id) as orders,
                sum(case when order_items.cost_price is null then null else (order_items.price - order_items.cost_price) * order_items.qty end) as profit')
            ->groupBy('order_items.product_id')->orderByDesc('revenue')->limit(20)->get()
            ->map(fn ($r) => (array) $r + ['margin' => $r->profit !== null && $r->revenue > 0 ? round($r->profit / $r->revenue * 100, 1) : null]);

        $views = Visit::whereBetween('created_at', $p->range())->whereNotNull('product_id')
            ->selectRaw('product_id, count(*) as views, count(distinct visitor_id) as visitors')->groupBy('product_id')
            ->orderByDesc('views')->limit(50)->get()->keyBy('product_id');
        $ordersByProduct = $this->itemsQuery($p->range())->whereIn('order_items.product_id', $views->keys())
            ->selectRaw('order_items.product_id, count(distinct order_items.order_id) as orders')->groupBy('order_items.product_id')->pluck('orders', 'product_id');
        $names = Product::whereIn('id', $views->keys())->pluck('name', 'id');
        $viewed = $views->map(fn ($v) => [
            'id'         => $v->product_id,
            'name'       => $names[$v->product_id] ?? '—',
            'views'      => (int) $v->views,
            'visitors'   => (int) $v->visitors,
            'orders'     => (int) ($ordersByProduct[$v->product_id] ?? 0),
            'conversion' => $v->visitors ? round(($ordersByProduct[$v->product_id] ?? 0) / $v->visitors * 100, 1) : 0,
        ])->values();

        $soldIds = $this->itemsQuery($p->range())->distinct()->pluck('order_items.product_id');
        $unsold = Product::active()->where('stock_qty', '>', 0)->whereNotIn('id', $soldIds);

        return response()->json([
            'period'   => $this->periodInfo($p),
            'top'      => $top,
            'viewed'   => $viewed->take(15),
            // Many views, few orders: price, photos or description may need work
            'attention' => $viewed->filter(fn ($v) => $v['visitors'] >= 10 && $v['conversion'] < 2)->sortByDesc('views')->take(8)->values(),
            'unsold'   => [
                'count' => (clone $unsold)->count(),
                'value' => (float) (clone $unsold)->selectRaw('sum(stock_qty * coalesce(cost_price, price)) as v')->value('v'),
                'items' => (clone $unsold)->orderByRaw('stock_qty * coalesce(cost_price, price) desc')->limit(10)->get(['id', 'name', 'stock_qty', 'price']),
            ],
            'by_brand' => $this->top($this->itemsQuery($p->range())
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->selectRaw("coalesce(brands.name, 'No brand') as name, sum(order_items.total) as revenue, sum(order_items.qty) as units")
                ->groupByRaw("coalesce(brands.name, 'No brand')")->orderByDesc('revenue')->get(), 'revenue', ['units']),
            'low_rated' => Product::active()->where('review_count', '>=', 2)->where('rating_avg', '<', 3.5)->orderBy('rating_avg')->limit(8)->get(['id', 'name', 'rating_avg', 'review_count']),
        ]);
    }

    // ───────────────────────── Customers ─────────────────────────
    public function customers(Request $request)
    {
        $p = Period::fromRequest($request);
        $phone = "substr(replace(replace(phone, '+', ''), '-', ''), -10)";

        // First order date for every phone number, to tell new buyers from returning ones
        $firstOrder = Order::whereNotIn('status', self::LOST)->selectRaw("{$phone} as ph, min(created_at) as first_at")->groupBy('ph')->pluck('first_at', 'ph');
        $buyers = Order::whereBetween('created_at', $p->range())->whereNotIn('status', self::LOST)
            ->selectRaw("{$phone} as ph, " . $p->bucketSql('created_at') . ' as k')->get();

        $series = collect($p->keys())->mapWithKeys(fn ($k) => [$k => ['key' => $k, 'new' => [], 'returning' => []]])->all();
        foreach ($buyers as $b) {
            $first = $firstOrder[$b->ph] ?? null;
            $isNew = $first && $first >= $p->from->format('Y-m-d H:i:s');
            if (isset($series[$b->k])) {
                $series[$b->k][$isNew ? 'new' : 'returning'][$b->ph] = true;
            }
        }
        $series = collect($series)->map(fn ($s) => ['key' => $s['key'], 'new' => count($s['new']), 'returning' => count($s['returning'])])->values();

        $uniqueBuyers = $buyers->pluck('ph')->unique();
        $newBuyers = $uniqueBuyers->filter(fn ($ph) => ($firstOrder[$ph] ?? '') >= $p->from->format('Y-m-d H:i:s'))->count();
        $allPhones = Order::whereNotIn('status', self::LOST)->selectRaw("{$phone} as ph, count(*) as n")->groupBy('ph')->pluck('n');
        $sold = Order::whereBetween('created_at', $p->range())->whereNotIn('status', self::LOST);

        return response()->json([
            'period' => $this->periodInfo($p),
            'totals' => [
                'buyers'        => $uniqueBuyers->count(),
                'new_buyers'    => $newBuyers,
                'returning'     => $uniqueBuyers->count() - $newBuyers,
                'new_accounts'  => User::where('role', 'customer')->whereBetween('created_at', $p->range())->count(),
                'repeat_rate'   => $allPhones->count() ? round($allPhones->filter(fn ($n) => $n >= 2)->count() / $allPhones->count() * 100, 1) : 0,
                'guest_share'   => ($c = (clone $sold)->count()) ? round((clone $sold)->whereNull('user_id')->count() / $c * 100, 1) : 0,
                'avg_lifetime'  => $allPhones->count() ? round((float) Order::whereNotIn('status', self::LOST)->sum('total') / $allPhones->count(), 2) : 0,
            ],
            'series' => $series,
            'top'    => (clone $sold)->selectRaw("{$phone} as ph, max(name) as name, max(phone) as phone, max(user_id) as user_id, max(city) as city, count(*) as orders, sum(total) as spent")
                ->groupBy('ph')->orderByDesc('spent')->limit(15)->get(),
            'by_city' => $this->top((clone $sold)->selectRaw('city as name, count(distinct ' . $phone . ') as buyers, sum(total) as revenue')->groupBy('city')->orderByDesc('revenue')->get(), 'revenue', ['buyers']),
        ]);
    }

    // ───────────────────────── Inventory ─────────────────────────
    public function inventory(Request $request)
    {
        $p = Period::fromRequest($request);
        $low = (int) Setting::get('inventory.low_stock_threshold', 5);

        // Daily sales rate over the last 30 days decides how long stock lasts
        $rate = $this->itemsQuery([now()->subDays(30), now()])->selectRaw('order_items.product_id, sum(order_items.qty) as units')
            ->groupBy('order_items.product_id')->pluck('units', 'product_id');
        $inStock = Product::active()->where('stock_qty', '>', 0)->get(['id', 'name', 'stock_qty', 'price', 'cost_price']);
        $cover = $inStock->filter(fn ($pr) => ($rate[$pr->id] ?? 0) > 0)
            ->map(fn ($pr) => ['id' => $pr->id, 'name' => $pr->name, 'stock' => $pr->stock_qty, 'per_day' => round($rate[$pr->id] / 30, 2), 'days' => (int) floor($pr->stock_qty / ($rate[$pr->id] / 30))])
            ->sortBy('days')->values();

        $lastSale = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->whereNotIn('orders.status', self::LOST)
            ->selectRaw('order_items.product_id, max(orders.created_at) as last_at')->groupBy('order_items.product_id')->pluck('last_at', 'product_id');
        $deadSince = now()->subDays(60)->format('Y-m-d H:i:s');
        $dead = $inStock->filter(fn ($pr) => ($lastSale[$pr->id] ?? '0') < $deadSince)
            ->map(fn ($pr) => ['id' => $pr->id, 'name' => $pr->name, 'stock' => $pr->stock_qty, 'value' => round($pr->stock_qty * ($pr->cost_price ?? $pr->price), 2), 'last_sale' => $lastSale[$pr->id] ?? null])
            ->sortByDesc('value')->values();

        $byCategory = DB::table('products')->where('products.stock_qty', '>', 0)
            ->join('categories as c', 'c.id', '=', 'products.category_id')->leftJoin('categories as parent', 'parent.id', '=', 'c.parent_id')
            ->selectRaw('coalesce(parent.name, c.name) as name, sum(products.stock_qty) as units, sum(products.stock_qty * coalesce(products.cost_price, 0)) as cost,
                sum(products.stock_qty * coalesce(products.sale_price, products.price)) as retail')
            ->groupByRaw('coalesce(parent.name, c.name)')->orderByDesc('retail')->get();

        return response()->json([
            'period'      => $this->periodInfo($p),
            'totals'      => [
                'units'      => (int) $inStock->sum('stock_qty'),
                'cost'       => round($inStock->sum(fn ($pr) => $pr->stock_qty * ($pr->cost_price ?? 0)), 2),
                'low'        => Product::active()->whereBetween('stock_qty', [1, $low])->count(),
                'out'        => Product::active()->where('stock_qty', '<=', 0)->count(),
                'dead_value' => round($dead->sum('value'), 2),
                'sold_units' => (int) $this->itemsQuery($p->range())->sum('order_items.qty'),
            ],
            'by_category' => $byCategory,
            'running_out' => $cover->take(10),
            'dead'        => $dead->take(10),
            'movements'   => DB::table('stock_movements')->whereBetween('created_at', $p->range())
                ->selectRaw('reason as name, sum(`change`) as units, count(*) as moves')->groupBy('reason')->get(),
        ]);
    }

    // ───────────────────────── Traffic ─────────────────────────
    public function traffic(Request $request)
    {
        $p = Period::fromRequest($request);
        $visits = fn ($range = null) => Visit::whereBetween('created_at', $range ?? $p->range());

        $totals = function ($range) use ($visits) {
            $sessions = $visits($range)->selectRaw('session_id, count(*) as views, min(created_at) as started, max(last_seen_at) as ended')->groupBy('session_id')->get();
            $count = $sessions->count();
            $durations = $sessions->map(fn ($s) => max(0, strtotime((string) $s->ended) - strtotime((string) $s->started)));
            $orders = Order::whereBetween('created_at', $range)->whereNotIn('status', self::LOST)->whereNotNull('visitor_id')->count();

            return [
                'visitors'      => $visits($range)->distinct()->count('visitor_id'),
                'sessions'      => $count,
                'pageviews'     => (int) $sessions->sum('views'),
                'pages_per'     => $count ? round($sessions->sum('views') / $count, 2) : 0,
                'bounce_rate'   => $count ? round($sessions->where('views', 1)->count() / $count * 100, 1) : 0,
                'avg_duration'  => $count ? (int) round($durations->avg()) : 0,
                'conversion'    => $count ? round($orders / $count * 100, 2) : 0,
            ];
        };
        $now = $totals($p->range());
        $before = $totals($p->previous());

        $series = $visits()->selectRaw($p->bucketSql('created_at') . ' as k, count(*) as views, count(distinct visitor_id) as visitors')->groupBy('k')->get()->keyBy('k');
        $count = fn (string $col, int $limit = 8) => $visits()->selectRaw("{$col} as name, count(distinct session_id) as sessions")
            ->whereNotNull($col)->groupBy($col)->orderByDesc('sessions')->limit($limit)->get();

        // Sessions, orders and sales for each traffic source
        $ordersBySource = Order::whereBetween('created_at', $p->range())->whereNotIn('status', self::LOST)->whereNotNull('traffic_source')
            ->selectRaw('traffic_source, count(*) as orders, sum(total) as revenue')->groupBy('traffic_source')->get()->keyBy('traffic_source');
        $sources = $count('source', 10)->map(fn ($s) => [
            'name'       => $s->name,
            'sessions'   => (int) $s->sessions,
            'orders'     => (int) ($ordersBySource[$s->name]->orders ?? 0),
            'revenue'    => (float) ($ordersBySource[$s->name]->revenue ?? 0),
            'conversion' => $s->sessions ? round(($ordersBySource[$s->name]->orders ?? 0) / $s->sessions * 100, 2) : 0,
        ]);

        // Landing page = first page of each session
        $firstIds = $visits()->selectRaw('min(id) as id')->groupBy('session_id');
        $landing = Visit::whereIn('id', $firstIds)->selectRaw('path, count(*) as sessions')->groupBy('path')->orderByDesc('sessions')->limit(10)->get();

        $funnelStep = fn (array $types) => $visits()->whereIn('page_type', $types)->distinct()->count('session_id');

        return response()->json([
            'period'   => $this->periodInfo($p),
            'totals'   => collect($now)->map(fn ($v, $k) => ['value' => $v, 'delta' => Period::delta($v, $before[$k] ?? 0)]),
            'series'   => collect($p->keys())->map(fn ($k) => ['key' => $k, 'visitors' => (int) ($series[$k]->visitors ?? 0), 'views' => (int) ($series[$k]->views ?? 0)]),
            'sources'  => $sources,
            'referrers' => $visits()->whereNotNull('source_name')->selectRaw('source_name as name, source, count(distinct session_id) as sessions')
                ->groupBy('source_name', 'source')->orderByDesc('sessions')->limit(10)->get(),
            'devices'  => $count('device', 3),
            'browsers' => $this->top($count('browser', 20), 'sessions'),
            'os'       => $this->top($count('os', 20), 'sessions'),
            'pages'    => $visits()->selectRaw('path, max(page_type) as type, count(*) as views, count(distinct visitor_id) as visitors')
                ->groupBy('path')->orderByDesc('views')->limit(15)->get(),
            'landing'  => $landing,
            'funnel'   => [
                ['step' => 'Visited the shop', 'sessions' => $now['sessions']],
                ['step' => 'Viewed a product', 'sessions' => $funnelStep(['product'])],
                ['step' => 'Opened the cart or checkout', 'sessions' => $funnelStep(['cart', 'checkout'])],
                ['step' => 'Reached checkout', 'sessions' => $funnelStep(['checkout'])],
                ['step' => 'Placed an order', 'sessions' => $visits()->where('page_type', 'order')->where('path', 'like', '/order-placed/%')->distinct()->count('session_id')],
            ],
        ]);
    }

    // ───────────────────────── Search & wishlist ─────────────────────────
    public function search(Request $request)
    {
        $p = Period::fromRequest($request);
        $logs = fn () => SearchLog::whereBetween('created_at', $p->range());
        $total = $logs()->count();
        $series = $logs()->selectRaw($p->bucketSql('created_at') . ' as k, count(*) as n, sum(case when results_count = 0 then 1 else 0 end) as zero')->groupBy('k')->get()->keyBy('k');

        $wishlist = DB::table('wishlists')->join('products', 'products.id', '=', 'wishlists.product_id')
            ->selectRaw('products.id, max(products.name) as name, max(products.stock_qty) as stock, count(*) as saves,
                sum(case when wishlists.created_at >= ? then 1 else 0 end) as recent', [$p->from])
            ->groupBy('products.id')->orderByDesc('saves')->limit(15)->get();

        return response()->json([
            'period' => $this->periodInfo($p),
            'totals' => [
                'searches'    => $total,
                'terms'       => $logs()->distinct()->count('query'),
                'zero_share'  => $total ? round($logs()->where('results_count', 0)->count() / $total * 100, 1) : 0,
                'searchers'   => $logs()->whereNotNull('visitor_id')->distinct()->count('visitor_id'),
                'saves'       => DB::table('wishlists')->whereBetween('created_at', $p->range())->count(),
                'wishlisters' => DB::table('wishlists')->distinct()->count('user_id'),
            ],
            'series' => collect($p->keys())->map(fn ($k) => ['key' => $k, 'searches' => (int) ($series[$k]->n ?? 0), 'zero' => (int) ($series[$k]->zero ?? 0)]),
            'top'    => $logs()->selectRaw('query, count(*) as searches, round(avg(results_count)) as results')->groupBy('query')->orderByDesc('searches')->limit(20)->get(),
            // What people looked for and could not find: products to add, or words to add to names
            'zero'   => $logs()->where('results_count', 0)->selectRaw('query, count(*) as searches, max(created_at) as last_at')->groupBy('query')->orderByDesc('searches')->limit(20)->get(),
            'wishlist' => $wishlist,
        ]);
    }

    // ───────────────────────── SEO ─────────────────────────
    public function seo(Request $request, SearchConsole $console)
    {
        $p = Period::fromRequest($request);
        $products = Product::active()->with('images:id,product_id,alt')->get(['id', 'name', 'slug', 'meta_title', 'meta_description', 'short_description', 'description']);

        $checks = [
            'no_meta'        => $products->filter(fn ($pr) => blank($pr->meta_description) && blank($pr->short_description)),
            'no_image'       => $products->filter(fn ($pr) => $pr->images->isEmpty()),
            'no_alt'         => $products->filter(fn ($pr) => $pr->images->isNotEmpty() && $pr->images->contains(fn ($i) => blank($i->alt))),
            'thin'           => $products->filter(fn ($pr) => mb_strlen(strip_tags((string) $pr->description)) < 150),
            'long_title'     => $products->filter(fn ($pr) => mb_strlen($pr->meta_title ?: $pr->name) > 65),
            'duplicate_name' => $products->groupBy(fn ($pr) => mb_strtolower(trim($pr->name)))->filter(fn ($g) => $g->count() > 1)->flatten(),
        ];
        $count = max(1, $products->count());
        $issues = collect($checks)->map(fn ($list, $key) => [
            'key'   => $key,
            'count' => $list->count(),
            'items' => $list->take(8)->map(fn ($pr) => ['id' => $pr->id, 'name' => $pr->name])->values(),
        ])->values();
        $healthy = $products->reject(fn ($pr) => collect($checks)->contains(fn ($list) => $list->contains('id', $pr->id)))->count();

        $organic = Visit::whereBetween('created_at', $p->range())->where('source', 'search')
            ->selectRaw($p->bucketSql('created_at') . ' as k, count(distinct session_id) as sessions')->groupBy('k')->pluck('sessions', 'k');

        return response()->json([
            'period'   => $this->periodInfo($p),
            'score'    => (int) round($healthy / $count * 100),
            'products' => $products->count(),
            'issues'   => $issues,
            'organic'  => [
                'sessions' => (int) $organic->sum(),
                'engines'  => Visit::whereBetween('created_at', $p->range())->where('source', 'search')
                    ->selectRaw('source_name as name, count(distinct session_id) as sessions')->groupBy('source_name')->orderByDesc('sessions')->get(),
                'series'   => collect($p->keys())->map(fn ($k) => ['key' => $k, 'sessions' => (int) ($organic[$k] ?? 0)]),
                'landing'  => Visit::whereBetween('created_at', $p->range())->where('source', 'search')
                    ->selectRaw('path, count(distinct session_id) as sessions')->groupBy('path')->orderByDesc('sessions')->limit(10)->get(),
            ],
            'sitemap'  => ['url' => url('/sitemap.xml'), 'robots' => url('/robots.txt')],
            'console'  => $console->report($p),
        ]);
    }

    // ───────────────────────── Live ─────────────────────────
    public function live()
    {
        $active = Visit::where('last_seen_at', '>=', now()->subMinutes(5));
        // The latest page of every active session
        $latest = Visit::whereIn('id', (clone $active)->selectRaw('max(id)')->groupBy('session_id'))
            ->with('product:id,name')->latest('last_seen_at')->limit(30)->get(['id', 'path', 'page_type', 'product_id', 'device', 'source', 'source_name', 'last_seen_at', 'created_at']);

        $minutes = Visit::where('last_seen_at', '>=', now()->subMinutes(30))->get(['session_id', 'created_at', 'last_seen_at']);
        $perMinute = collect(range(29, 0))->map(function ($ago) use ($minutes) {
            $t = now()->subMinutes($ago);

            return [
                'key'      => $t->format('H:i'),
                'visitors' => $minutes->filter(fn ($v) => $v->created_at <= $t && $v->last_seen_at >= $t->copy()->subMinute())->unique('session_id')->count(),
            ];
        });

        $today = Order::whereDate('created_at', today())->whereNotIn('status', self::LOST);

        return response()->json([
            'now'      => (clone $active)->distinct()->count('session_id'),
            'pages'    => $latest,
            'devices'  => (clone $active)->selectRaw('device as name, count(distinct session_id) as sessions')->groupBy('device')->get(),
            'per_minute' => $perMinute,
            'today'    => [
                'visitors'  => Visit::whereDate('created_at', today())->distinct()->count('visitor_id'),
                'pageviews' => Visit::whereDate('created_at', today())->count(),
                'orders'    => (clone $today)->count(),
                'revenue'   => (float) (clone $today)->sum('total'),
            ],
            'recent_orders' => Order::latest()->limit(6)->get(['id', 'order_number', 'name', 'city', 'total', 'status', 'created_at']),
            'vitals'   => $this->vitals(),
        ]);
    }

    /** Core Web Vitals from real visitors over the last 7 days (75th percentile, like Google). */
    private function vitals(): array
    {
        $rows = Visit::where('created_at', '>=', now()->subDays(7))
            ->where(fn ($q) => $q->whereNotNull('lcp')->orWhereNotNull('inp')->orWhereNotNull('cls')->orWhereNotNull('ttfb'))
            ->latest('id')->limit(5000)->get(['path', 'device', 'lcp', 'inp', 'cls', 'ttfb']);

        $summary = fn (Collection $set) => [
            'samples' => $set->count(),
            'lcp'     => Period::p75($set->pluck('lcp')->all()),
            'inp'     => Period::p75($set->pluck('inp')->all()),
            'cls'     => Period::p75($set->pluck('cls')->all()),
            'ttfb'    => Period::p75($set->pluck('ttfb')->all()),
        ];

        return [
            'all'     => $summary($rows),
            'devices' => $rows->groupBy('device')->map($summary),
            'slowest' => $rows->whereNotNull('lcp')->groupBy(fn ($r) => preg_replace('/\?.*$/', '', $r->path))
                ->filter(fn ($g) => $g->count() >= 3)
                ->map(fn ($g, $path) => ['path' => $path, 'samples' => $g->count(), 'lcp' => Period::p75($g->pluck('lcp')->all())])
                ->sortByDesc('lcp')->take(8)->values(),
        ];
    }

    /** Lab test of one page with Google PageSpeed Insights (cached for an hour). */
    public function pagespeed(Request $request)
    {
        $data = $request->validate([
            'path'     => ['required', 'string', 'max:300', 'starts_with:/'],
            'strategy' => ['required', 'in:mobile,desktop'],
        ]);
        $url = url($data['path']);
        if (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1') || str_ends_with(parse_url($url, PHP_URL_HOST) ?? '', '.test')) {
            return response()->json(['message' => 'PageSpeed can only test a website that is live on the internet. Run it after the site is published.'], 422);
        }

        return Cache::remember('psi:' . md5($url . $data['strategy']), 3600, function () use ($url, $data) {
            $res = Http::timeout(60)->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', array_filter([
                'url'      => $url,
                'strategy' => $data['strategy'],
                'category' => 'performance',
                'key'      => Setting::get('integrations.pagespeed_key') ?: null,
            ]));
            abort_unless($res->ok(), 502, 'PageSpeed could not test the page: ' . ($res->json('error.message') ?? 'no answer'));

            $audits = $res->json('lighthouseResult.audits', []);
            $metric = fn ($id) => ['value' => $audits[$id]['displayValue'] ?? null, 'score' => $audits[$id]['score'] ?? null];

            return [
                'url'      => $url,
                'strategy' => $data['strategy'],
                'score'    => (int) round(($res->json('lighthouseResult.categories.performance.score') ?? 0) * 100),
                'metrics'  => [
                    'fcp' => $metric('first-contentful-paint'),
                    'lcp' => $metric('largest-contentful-paint'),
                    'tbt' => $metric('total-blocking-time'),
                    'cls' => $metric('cumulative-layout-shift'),
                    'si'  => $metric('speed-index'),
                ],
                'opportunities' => collect($audits)->filter(fn ($a) => ($a['details']['type'] ?? null) === 'opportunity' && ($a['score'] ?? 1) < 0.9)
                    ->sortByDesc(fn ($a) => $a['details']['overallSavingsMs'] ?? 0)->take(6)
                    ->map(fn ($a) => ['title' => $a['title'], 'saving' => $a['displayValue'] ?? null])->values(),
                'tested_at' => now()->toIso8601String(),
            ];
        });
    }

    // ───────────────────────── helpers ─────────────────────────
    private function periodInfo(Period $p): array
    {
        return ['from' => $p->from->toIso8601String(), 'to' => $p->to->toIso8601String(), 'bucket' => $p->bucket];
    }

    /** Keep the biggest few rows and fold the rest into "Other". */
    private function top(Collection $rows, string $measure, array $sum = [], int $keep = 5): Collection
    {
        // Models must use toArray(); casting a model with (array) exposes its internals instead of its fields
        $rows = $rows->map(fn ($r) => $r instanceof \Illuminate\Database\Eloquent\Model ? $r->toArray() : (array) $r);
        if ($rows->count() <= $keep + 1) {
            return $rows->values();
        }
        $other = ['name' => 'Other', $measure => $rows->slice($keep)->sum($measure)];
        foreach ($sum as $col) {
            $other[$col] = $rows->slice($keep)->sum($col);
        }

        return $rows->take($keep)->push($other)->values();
    }
}
