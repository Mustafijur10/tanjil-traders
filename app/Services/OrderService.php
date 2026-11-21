<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Everything about turning a cart into an order and moving it along:
 * live prices and stock for cart lines, totals with delivery and coupon,
 * placing the order (stock goes out), and status changes (stock comes back on cancel).
 * The storefront checkout and the admin "Create order" page both go through here.
 */
class OrderService
{
    /**
     * Current price and stock for cart lines.
     *
     * @param  array<int, array{product_id:int, variant_id?:int|null, qty:int}>  $lines
     * @return array{lines: Collection, subtotal: float, issues: array}
     */
    public function priceCart(array $lines): array
    {
        $productIds = collect($lines)->pluck('product_id')->filter()->unique();
        $products = Product::whereIn('id', $productIds)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order'), 'variants.values'])
            ->get()->keyBy('id');

        $issues = [];
        $priced = collect($lines)->map(function ($line) use ($products, &$issues) {
            $qty = max(1, (int) ($line['qty'] ?? 1));
            $variantId = $line['variant_id'] ?? null;
            $p = $products->get($line['product_id'] ?? 0);
            $v = $p && $variantId ? $p->variants->firstWhere('id', (int) $variantId) : null;
            $key = ($line['product_id'] ?? 0) . ':' . ($variantId ?: 0);

            // Gone, unpublished, or the chosen option no longer exists
            $broken = ! $p || $p->status !== 'active'
                || ($variantId && (! $v || ! $v->is_active))
                || (! $variantId && $p->variants->where('is_active', true)->isNotEmpty());
            if ($broken) {
                $issues[] = ['key' => $key, 'type' => 'unavailable', 'name' => $p->name ?? null];

                return ['key' => $key, 'product_id' => (int) ($line['product_id'] ?? 0), 'variant_id' => $variantId ? (int) $variantId : null,
                    'name' => $p->name ?? null, 'available' => false, 'qty' => $qty, 'stock' => 0, 'price' => 0, 'total' => 0];
            }

            $stock = $v ? $v->stock_qty : $p->stock_qty;
            $price = $v ? $this->variantPrice($p, $v) : $p->final_price;
            $regular = $v ? $v->price : $p->price;

            if ($stock <= 0) {
                $issues[] = ['key' => $key, 'type' => 'out_of_stock', 'name' => $p->name];
            } elseif ($qty > $stock) {
                $issues[] = ['key' => $key, 'type' => 'qty_reduced', 'name' => $p->name, 'stock' => $stock];
                $qty = $stock;
            }

            return [
                'key'           => $key,
                'product_id'    => $p->id,
                'variant_id'    => $v?->id,
                'name'          => $p->name,
                'slug'          => $p->slug,
                'variant_label' => $v?->label() ?: null,
                'sku'           => $v?->sku ?: $p->sku,
                'image'         => $v?->image ?: $p->images->first()?->path,
                'price'         => (float) $price,
                'regular_price' => (float) $regular,
                'cost_price'    => $p->cost_price,
                'qty'           => $qty,
                'stock'         => $stock,
                'available'     => $stock > 0,
                'total'         => $stock > 0 ? round($price * $qty, 2) : 0,
            ];
        })->values();

        return [
            'lines'    => $priced,
            'subtotal' => round($priced->where('available', true)->sum('total'), 2),
            'issues'   => $issues,
        ];
    }

    /** Variant price, honouring the product's sale end date. */
    public function variantPrice(Product $p, ProductVariant $v): float
    {
        $saleLive = $v->sale_price !== null && $v->sale_price < $v->price
            && ($p->sale_ends_at === null || $p->sale_ends_at->isFuture());

        return $saleLive ? $v->sale_price : $v->price;
    }

    /**
     * Full totals for checkout.
     *
     * @return array{lines: Collection, subtotal: float, issues: array, shipping: ?ShippingMethod, shipping_cost: float, coupon: ?Coupon, coupon_error: ?string, discount: float, total: float}
     */
    public function quote(array $lines, ?int $shippingMethodId, ?string $couponCode, ?float $shippingOverride = null): array
    {
        $cart = $this->priceCart($lines);
        $subtotal = $cart['subtotal'];

        $shipping = $shippingMethodId ? ShippingMethod::where('is_active', true)->find($shippingMethodId) : null;
        $shippingCost = $shippingOverride ?? ($shipping ? $shipping->chargeFor($subtotal) : 0.0);

        $coupon = null;
        $couponError = null;
        $discount = 0.0;
        if ($couponCode = trim((string) $couponCode)) {
            $coupon = Coupon::whereRaw('upper(code) = ?', [mb_strtoupper($couponCode)])->first();
            $couponError = $coupon ? $coupon->problemFor($subtotal) : 'This coupon code is not valid.';
            if ($couponError) {
                $coupon = null;
            } else {
                $discount = $coupon->discountFor($subtotal);
            }
        }

        return $cart + [
            'shipping'      => $shipping,
            'shipping_cost' => round($shippingCost, 2),
            'coupon'        => $coupon,
            'coupon_error'  => $couponError,
            'discount'      => $discount,
            'total'         => round(max(0, $subtotal - $discount) + $shippingCost, 2),
        ];
    }

    /**
     * Place an order. $data holds the customer and delivery fields, `items`, `shipping_method_id`,
     * `coupon_code`, `payment_method`, `transaction_id`. Staff may also pass `shipping_cost`,
     * `discount`, `status` and `payment_status`.
     */
    public function place(array $data, ?User $customer, string $source = 'web', ?User $staff = null): Order
    {
        return DB::transaction(function () use ($data, $customer, $source, $staff) {
            // Lock the stock rows so two buyers cannot take the last unit at the same time
            $lines = collect($data['items']);
            Product::whereIn('id', $lines->pluck('product_id'))->lockForUpdate()->get(['id']);
            ProductVariant::whereIn('id', $lines->pluck('variant_id')->filter())->lockForUpdate()->get(['id']);

            $quote = $this->quote($data['items'], $data['shipping_method_id'] ?? null, $data['coupon_code'] ?? null, $data['shipping_cost'] ?? null);

            if ($quote['issues']) {
                throw ValidationException::withMessages(['items' => $this->issueMessage($quote['issues'][0])]);
            }
            if ($quote['lines']->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Your cart is empty.']);
            }
            if ($quote['coupon_error']) {
                throw ValidationException::withMessages(['coupon_code' => $quote['coupon_error']]);
            }

            $discount = isset($data['discount']) ? min((float) $data['discount'], $quote['subtotal']) : $quote['discount'];
            $total = round($quote['subtotal'] - $discount + $quote['shipping_cost'], 2);

            $order = Order::create([
                'user_id'            => $customer?->id,
                'name'               => $data['name'],
                'phone'              => $data['phone'],
                'email'              => $data['email'] ?? null,
                'address'            => $data['address'],
                'city'               => $data['city'],
                'note'               => $data['note'] ?? null,
                'shipping_method_id' => $quote['shipping']?->id,
                'subtotal'           => $quote['subtotal'],
                'shipping_cost'      => $quote['shipping_cost'],
                'coupon_code'        => $quote['coupon']?->code,
                'discount'           => $discount,
                'total'              => $total,
                'payment_method'     => $data['payment_method'] ?? 'cod',
                'payment_status'     => $data['payment_status'] ?? 'unpaid',
                'transaction_id'     => $data['transaction_id'] ?? null,
                'status'             => 'pending',
                'source'             => $source,
            ]);

            foreach ($quote['lines'] as $line) {
                $order->items()->create([
                    'product_id'    => $line['product_id'],
                    'variant_id'    => $line['variant_id'],
                    'name'          => $line['name'],
                    'variant_label' => $line['variant_label'],
                    'sku'           => $line['sku'],
                    'price'         => $line['price'],
                    'cost_price'    => $line['cost_price'],
                    'qty'           => $line['qty'],
                    'total'         => $line['total'],
                ]);
                $product = Product::find($line['product_id']);
                $variant = $line['variant_id'] ? ProductVariant::find($line['variant_id']) : null;
                StockMovement::record($product, $variant, -$line['qty'], 'sale', ['order_id' => $order->id, 'user_id' => $staff?->id]);
            }

            $quote['coupon']?->increment('used_count');

            $order->history()->create([
                'status'  => 'pending',
                'note'    => $source === 'admin' ? 'Order created by staff.' : 'Order placed on the website.',
                'user_id' => $staff?->id,
            ]);

            if (($data['status'] ?? 'pending') === 'confirmed') {
                $this->changeStatus($order, 'confirmed', null, $staff);
            }

            return $order->fresh(['items', 'history']);
        });
    }

    /** Move an order to its next status, with the stock and date side effects. */
    public function changeStatus(Order $order, string $status, ?string $note = null, ?User $by = null): Order
    {
        if (! $order->canMoveTo($status)) {
            throw ValidationException::withMessages(['status' => "An order that is {$order->status} cannot be marked {$status}."]);
        }

        return DB::transaction(function () use ($order, $status, $note, $by) {
            $changes = ['status' => $status];

            if ($status === 'shipped') {
                $changes['shipped_at'] = now();
            }
            if ($status === 'delivered') {
                $changes['delivered_at'] = now();
                // Cash on delivery is paid when the parcel is handed over
                if ($order->payment_method === 'cod' && $order->payment_status === 'unpaid') {
                    $changes['payment_status'] = 'paid';
                }
            }
            if ($status === 'cancelled') {
                $this->restock($order, 'cancel', $by);
                $order->coupon_code && Coupon::where('code', $order->coupon_code)->where('used_count', '>', 0)->decrement('used_count');
            }

            $order->update($changes);
            $order->history()->create(['status' => $status, 'note' => $note, 'user_id' => $by?->id]);

            return $order;
        });
    }

    /** Put every item of an order back on the shelf (products or options deleted since are skipped). */
    public function restock(Order $order, string $reason, ?User $by = null, ?array $qtyByItem = null): void
    {
        foreach ($order->items()->with(['product', 'variant'])->get() as $item) {
            $qty = $qtyByItem === null ? $item->qty : (int) ($qtyByItem[$item->id] ?? 0);
            if ($qty <= 0 || ! $item->product || ($item->variant_id && ! $item->variant)) {
                continue;
            }
            StockMovement::record($item->product, $item->variant, $qty, $reason, ['order_id' => $order->id, 'user_id' => $by?->id]);
        }
    }

    public function issueMessage(array $issue): string
    {
        return match ($issue['type']) {
            'out_of_stock' => "{$issue['name']} is out of stock. Please remove it from your cart.",
            'qty_reduced'  => "Only {$issue['stock']} of {$issue['name']} left. We changed the quantity.",
            default        => 'An item in your cart is no longer available. Please remove it.',
        };
    }

    /** Payment methods the shop has switched on, with the number to send money to. */
    public static function paymentMethods(): array
    {
        $s = Setting::cached();

        return collect([
            ['value' => 'cod', 'label' => 'Cash on delivery', 'enabled' => (bool) ($s['checkout.cod_enabled'] ?? true), 'number' => null],
            ['value' => 'bkash', 'label' => 'bKash', 'enabled' => (bool) ($s['checkout.bkash_enabled'] ?? false), 'number' => $s['checkout.bkash_number'] ?? null],
            ['value' => 'nagad', 'label' => 'Nagad', 'enabled' => (bool) ($s['checkout.nagad_enabled'] ?? false), 'number' => $s['checkout.nagad_number'] ?? null],
        ])->where('enabled', true)->values()->all();
    }
}
