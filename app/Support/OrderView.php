<?php

namespace App\Support;

use App\Models\Order;

/**
 * What a customer may see about their order (tracking page, account, order placed page).
 * No cost prices, no staff names or private notes.
 */
class OrderView
{
    public static function make(Order $order): array
    {
        $order->loadMissing(['items.product.images', 'shippingMethod', 'courier', 'history', 'returns.items']);

        return [
            'number'         => $order->order_number,
            'status'         => $order->status,
            'created_at'     => $order->created_at?->toIso8601String(),
            'shipped_at'     => $order->shipped_at?->toIso8601String(),
            'delivered_at'   => $order->delivered_at?->toIso8601String(),
            'name'           => $order->name,
            'phone'          => $order->phone,
            'email'          => $order->email,
            'address'        => $order->address,
            'city'           => $order->city,
            'note'           => $order->note,
            'items'          => $order->items->map(fn ($i) => [
                'id'            => $i->id,
                'name'          => $i->name,
                'variant_label' => $i->variant_label,
                'slug'          => $i->product?->status === 'active' ? $i->product->slug : null,
                'image'         => $i->product?->images->first()?->path,
                'price'         => $i->price,
                'qty'           => $i->qty,
                'total'         => $i->total,
            ])->values(),
            'subtotal'       => $order->subtotal,
            'shipping_cost'  => $order->shipping_cost,
            'shipping'       => $order->shippingMethod?->only(['name', 'delivery_time']),
            'coupon_code'    => $order->coupon_code,
            'discount'       => $order->discount,
            'total'          => $order->total,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'transaction_id' => $order->transaction_id,
            'courier'        => $order->courier?->name,
            'tracking_code'  => $order->tracking_code,
            'tracking_link'  => $order->trackingLink(),
            // Status steps only; staff notes stay private
            'timeline'       => $order->history->sortBy('id')->map(fn ($h) => ['status' => $h->status, 'at' => $h->created_at?->toIso8601String()])->values(),
            'returns'        => $order->returns->map(fn ($r) => [
                'id'            => $r->id,
                'status'        => $r->status,
                'reason'        => $r->reason,
                'refund_amount' => $r->refund_amount,
                'created_at'    => $r->created_at?->toIso8601String(),
                'items'         => $r->items->map->only(['order_item_id', 'qty']),
            ])->values(),
            'can_cancel'     => $order->customerCanCancel(),
            'can_return'     => $order->customerCanReturn(),
        ];
    }
}
