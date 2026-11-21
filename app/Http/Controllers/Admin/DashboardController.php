<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Real numbers for the dashboard cards. Each card: value now, value in the previous
 * period of the same length, a trend series for the sparkline, and one extra fact for the footer.
 */
class DashboardController extends Controller
{
    /** Orders that count as sales. */
    private const SOLD = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];

    public function kpis(Request $request)
    {
        $range = $request->validate(['range' => ['nullable', 'in:7,30,12']])['range'] ?? '30';

        $now = CarbonImmutable::now();
        [$from, $prevFrom, $bucket] = match ($range) {
            '7'  => [$now->subDays(6)->startOfDay(), $now->subDays(13)->startOfDay(), 'day'],
            '12' => [$now->subMonths(11)->startOfMonth(), $now->subMonths(23)->startOfMonth(), 'month'],
            default => [$now->subDays(29)->startOfDay(), $now->subDays(59)->startOfDay(), 'day'],
        };
        $prevTo = $from->subSecond();

        $sales = fn ($a, $b) => Order::query()->whereIn('status', self::SOLD)->whereBetween('created_at', [$a, $b]);
        $revenue = (float) $sales($from, $now)->sum('total');
        $prevRevenue = (float) $sales($prevFrom, $prevTo)->sum('total');
        $orders = $sales($from, $now)->count();
        $prevOrders = $sales($prevFrom, $prevTo)->count();

        $newCustomers = User::where('role', 'customer')->whereBetween('created_at', [$from, $now])->count();
        $prevNewCustomers = User::where('role', 'customer')->whereBetween('created_at', [$prevFrom, $prevTo])->count();

        $visitors = Visit::whereBetween('created_at', [$from, $now])->distinct('visitor_id')->count('visitor_id');
        $prevVisitors = Visit::whereBetween('created_at', [$prevFrom, $prevTo])->distinct('visitor_id')->count('visitor_id');

        $returned = fn ($a, $b) => Order::where('status', 'returned')->whereBetween('created_at', [$a, $b])->count();
        $finished = fn ($a, $b) => Order::whereIn('status', ['delivered', 'returned'])->whereBetween('created_at', [$a, $b])->count();

        $low = (int) Setting::get('inventory.low_stock_threshold', 5);
        $active = Product::active();

        $series = fn (string $expr, $query) => $this->series($query, $expr, $from, $now, $bucket);
        $revenueSeries = $series('sum(total)', Order::whereIn('status', self::SOLD));
        $orderSeries = $series('count(*)', Order::whereIn('status', self::SOLD));

        $pendingOldest = Order::where('status', 'pending')->min('created_at');

        return response()->json([
            'range' => $range,
            'cards' => [
                'revenue' => [
                    'value' => $revenue, 'previous' => $prevRevenue, 'series' => $revenueSeries,
                    'meta'  => ['today' => (float) $sales($now->startOfDay(), $now)->sum('total')],
                ],
                'orders' => [
                    'value' => $orders, 'previous' => $prevOrders, 'series' => $orderSeries,
                    'meta'  => ['today' => $sales($now->startOfDay(), $now)->count()],
                ],
                'products' => [
                    'value'    => (clone $active)->count(),
                    'previous' => null,
                    'series'   => $series('count(*)', Product::query()),
                    'meta'     => [
                        'added'   => Product::whereBetween('created_at', [$from, $now])->count(),
                        'drafts'  => Product::where('status', 'draft')->count(),
                        // Stock health of published products, for the donut
                        'healthy' => (clone $active)->where('stock_qty', '>', $low)->count(),
                        'low'     => (clone $active)->whereBetween('stock_qty', [1, $low])->count(),
                        'out'     => (clone $active)->where('stock_qty', '<=', 0)->count(),
                    ],
                ],
                'customers' => [
                    'value'    => User::where('role', 'customer')->count(),
                    'previous' => null,
                    'series'   => $series('count(*)', User::where('role', 'customer')),
                    'meta'     => ['new' => $newCustomers, 'new_previous' => $prevNewCustomers],
                ],
                'aov' => [
                    'value'    => $orders ? round($revenue / $orders) : 0,
                    'previous' => $prevOrders ? round($prevRevenue / $prevOrders) : 0,
                    'series'   => collect($revenueSeries)->zip($orderSeries)->map(fn ($p) => $p[1] ? round($p[0] / $p[1]) : 0)->all(),
                    'meta'     => ['highest' => (float) $sales($from, $now)->max('total')],
                ],
                'conversion' => [
                    'value'    => $visitors ? round($orders / $visitors * 100, 1) : 0,
                    'previous' => $prevVisitors ? round($prevOrders / $prevVisitors * 100, 1) : 0,
                    'series'   => $series('count(distinct visitor_id)', Visit::query()),
                    'meta'     => ['visitors' => $visitors],
                ],
                'pending' => [
                    'value'    => Order::where('status', 'pending')->count(),
                    'previous' => null,
                    'series'   => $series('count(*)', Order::where('status', 'pending')),
                    'meta'     => ['oldest_hours' => $pendingOldest ? (int) CarbonImmutable::parse($pendingOldest)->diffInHours($now) : null],
                ],
                'returns' => [
                    'value'    => ($f = $finished($from, $now)) ? round($returned($from, $now) / $f * 100, 1) : 0,
                    'previous' => ($pf = $finished($prevFrom, $prevTo)) ? round($returned($prevFrom, $prevTo) / $pf * 100, 1) : 0,
                    'series'   => $series('count(*)', Order::where('status', 'returned')),
                    'meta'     => ['returned' => $returned($from, $now)],
                ],
                'low_stock' => [
                    'value'    => (clone $active)->whereBetween('stock_qty', [1, $low])->count(),
                    'previous' => null,
                    'series'   => [],
                    'meta'     => ['out' => (clone $active)->where('stock_qty', '<=', 0)->count(), 'threshold' => $low],
                ],
            ],
        ]);
    }

    /** One number per day (or month) from $from to $to, zero where nothing happened. */
    private function series($query, string $expr, CarbonImmutable $from, CarbonImmutable $to, string $bucket): array
    {
        $driver = DB::connection()->getDriverName();
        $fmt = $bucket === 'month' ? '%Y-%m' : '%Y-%m-%d';
        $key = $driver === 'sqlite' ? "strftime('{$fmt}', created_at)" : "DATE_FORMAT(created_at, '{$fmt}')";

        $rows = $query->whereBetween('created_at', [$from, $to])
            ->selectRaw("{$key} as k, {$expr} as v")
            ->groupBy('k')
            ->pluck('v', 'k');

        $step = $bucket === 'month' ? '1 month' : '1 day';
        $phpFmt = $bucket === 'month' ? 'Y-m' : 'Y-m-d';

        return collect(CarbonPeriod::create($from, $step, $to))
            ->map(fn ($d) => (float) ($rows[$d->format($phpFmt)] ?? 0))
            ->values()
            ->all();
    }
}
