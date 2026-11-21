<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\FlashSale;
use App\Models\Order;
use App\Services\FlashSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Coupons and flash sales, with how well each one sold. */
class PromotionController extends Controller
{
    // ── Coupons ──
    public function coupons()
    {
        $stats = Order::query()
            ->whereNotNull('coupon_code')->where('status', '!=', 'cancelled')
            ->selectRaw('upper(coupon_code) as code, count(*) as orders, sum(discount) as discount, sum(total) as revenue')
            ->groupByRaw('upper(coupon_code)')
            ->get()->keyBy('code');

        $items = Coupon::orderByDesc('created_at')->get()->map(function (Coupon $c) use ($stats) {
            $s = $stats[mb_strtoupper($c->code)] ?? null;

            return $c->toArray() + [
                'state'    => $this->couponState($c),
                'orders'   => (int) ($s->orders ?? 0),
                'discount' => (float) ($s->discount ?? 0),
                'revenue'  => (float) ($s->revenue ?? 0),
            ];
        });

        return response()->json([
            'items' => $items,
            'stats' => [
                'active'   => $items->where('state', 'active')->count(),
                'orders'   => $items->sum('orders'),
                'discount' => $items->sum('discount'),
                'revenue'  => $items->sum('revenue'),
            ],
        ]);
    }

    public function storeCoupon(Request $request)
    {
        return response()->json(Coupon::create($this->couponData($request)), 201);
    }

    public function updateCoupon(Request $request, Coupon $coupon)
    {
        $coupon->update($this->couponData($request, $coupon));

        return $coupon;
    }

    public function destroyCoupon(Coupon $coupon)
    {
        $coupon->delete(); // past orders keep the code and discount

        return response()->noContent();
    }

    private function couponState(Coupon $c): string
    {
        return match (true) {
            ! $c->is_active                                                   => 'paused',
            $c->ends_at && $c->ends_at->isPast()                              => 'expired',
            $c->usage_limit !== null && $c->used_count >= $c->usage_limit     => 'used_up',
            $c->starts_at && $c->starts_at->isFuture()                        => 'scheduled',
            default                                                           => 'active',
        };
    }

    private function couponData(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['code' => mb_strtoupper(preg_replace('/\s+/', '', (string) $request->input('code')))]);

        return $request->validate([
            'code'         => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'type'         => ['required', 'in:fixed,percent'],
            'value'        => ['required', 'numeric', 'gt:0', Rule::when($request->type === 'percent', 'max:100')],
            'min_order'    => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit'  => ['nullable', 'integer', 'min:1'],
            'starts_at'    => ['nullable', 'date'],
            'ends_at'      => ['nullable', 'date', Rule::when($request->filled('starts_at'), 'after:starts_at')],
            'is_active'    => ['boolean'],
        ], ['code.alpha_dash' => 'Use only letters, numbers, dashes and underscores in the code.']);
    }

    // ── Flash sales ──
    public function flashSales()
    {
        $sales = FlashSale::withCount('products')->orderByDesc('starts_at')->get();

        // Units and revenue sold from each sale's products while it ran
        $sales->each(function (FlashSale $s) {
            $sold = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('flash_sale_items', fn ($j) => $j->on('flash_sale_items.product_id', '=', 'order_items.product_id')->where('flash_sale_items.flash_sale_id', $s->id))
                ->whereNotIn('orders.status', ['cancelled'])
                ->whereBetween('orders.created_at', [$s->starts_at, min($s->ends_at, now())])
                ->selectRaw('coalesce(sum(order_items.qty), 0) as units, coalesce(sum(order_items.total), 0) as revenue')
                ->first();
            $s->setAttribute('units', (int) $sold->units);
            $s->setAttribute('revenue', (float) $sold->revenue);
        });

        return $sales;
    }

    public function showFlashSale(FlashSale $flashSale)
    {
        return $flashSale->load(['products' => fn ($q) => $q->select('products.id', 'name', 'price', 'sale_price', 'stock_qty')->with('images')]);
    }

    public function storeFlashSale(Request $request, FlashSaleService $service)
    {
        return response()->json($service->save($this->flashData($request)), 201);
    }

    public function updateFlashSale(Request $request, FlashSale $flashSale, FlashSaleService $service)
    {
        return $service->save($this->flashData($request), $flashSale);
    }

    public function destroyFlashSale(FlashSale $flashSale, FlashSaleService $service)
    {
        $service->delete($flashSale);

        return response()->noContent();
    }

    private function flashData(Request $request): array
    {
        return $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'discount_type'  => ['required', 'in:percent,fixed'],
            'discount_value' => ['required', 'numeric', 'gt:0', Rule::when($request->discount_type === 'percent', 'max:90')],
            'starts_at'      => ['required', 'date'],
            'ends_at'        => ['required', 'date', 'after:starts_at'],
            'is_active'      => ['boolean'],
            'product_ids'    => ['required', 'array', 'min:1', 'max:200'],
            'product_ids.*'  => ['integer', 'exists:products,id'],
        ], ['product_ids.required' => 'Add at least one product.']);
    }
}
