<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Returns and refunds:
 *   requested -> approved -> received -> refunded      (or rejected before "received")
 * Stock comes back when the items are received (if "restock" is on, i.e. they can be sold again).
 * When everything in the order has been refunded the order is marked "returned" and "refunded".
 */
class ReturnService
{
    public const NEXT = [
        'requested' => ['approved', 'rejected'],
        'approved'  => ['received', 'rejected'],
        'received'  => ['refunded'],
        'refunded'  => [],
        'rejected'  => [],
    ];

    public function __construct(private OrderService $orders) {}

    /** How many of each order item can still be returned: [order_item_id => qty]. */
    public function returnable(Order $order): array
    {
        $taken = DB::table('order_return_items')
            ->join('order_returns', 'order_returns.id', '=', 'order_return_items.order_return_id')
            ->where('order_returns.order_id', $order->id)
            ->where('order_returns.status', '!=', 'rejected')
            ->groupBy('order_item_id')
            ->pluck(DB::raw('sum(qty)'), 'order_item_id');

        return $order->items->mapWithKeys(fn ($i) => [$i->id => max(0, $i->qty - (int) ($taken[$i->id] ?? 0))])->all();
    }

    /** @param array<int, array{order_item_id:int, qty:int}> $items */
    public function create(Order $order, array $items, string $reason, ?string $details, ?User $by = null): OrderReturn
    {
        if (! in_array($order->status, ['delivered', 'returned'], true)) {
            throw ValidationException::withMessages(['items' => 'Only delivered orders can be returned.']);
        }

        $left = $this->returnable($order);
        $items = collect($items)->filter(fn ($i) => (int) $i['qty'] > 0)->values();
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Choose at least one item to return.']);
        }
        foreach ($items as $i) {
            if (! array_key_exists($i['order_item_id'], $left) || $i['qty'] > $left[$i['order_item_id']]) {
                throw ValidationException::withMessages(['items' => 'One of the items cannot be returned in that quantity.']);
            }
        }

        return DB::transaction(function () use ($order, $items, $reason, $details, $by) {
            $return = $order->returns()->create(['reason' => $reason, 'details' => $details, 'status' => 'requested']);
            foreach ($items as $i) {
                $return->items()->create(['order_item_id' => $i['order_item_id'], 'qty' => $i['qty']]);
            }
            $return->refund_amount = $return->load('items.orderItem')->itemsValue();
            $return->save();

            $order->history()->create(['status' => $order->status, 'note' => 'Return requested (#' . $return->id . ').', 'user_id' => $by?->id]);

            return $return;
        });
    }

    public function changeStatus(OrderReturn $return, string $status, array $extra = [], ?User $by = null): OrderReturn
    {
        if (! in_array($status, self::NEXT[$return->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "A {$return->status} return cannot be marked {$status}."]);
        }

        return DB::transaction(function () use ($return, $status, $extra, $by) {
            $order = $return->order;
            $return->fill(array_intersect_key($extra, array_flip(['refund_amount', 'refund_method', 'restock', 'admin_note'])));
            $return->status = $status;
            $return->save();

            if ($status === 'received' && $return->restock) {
                $qty = $return->items->pluck('qty', 'order_item_id')->all();
                $this->orders->restock($order, 'return', $by, $qty);
            }

            if ($status === 'refunded') {
                $refunded = $order->returns()->where('status', 'refunded')->sum('refund_amount');
                $allBack = collect($this->returnable($order->load('items')))->sum() === 0
                    && $order->returns()->whereIn('status', ['requested', 'approved', 'received'])->doesntExist();
                $order->update([
                    'payment_status' => $refunded >= $order->total - 0.01 || $allBack ? 'refunded' : $order->payment_status,
                    'status'         => $allBack && $order->status === 'delivered' ? 'returned' : $order->status,
                ]);
            }

            $note = "Return #{$return->id} {$status}" . ($status === 'refunded' && $return->refund_amount ? ' (' . number_format($return->refund_amount, 2) . ')' : '') . '.';
            $order->history()->create(['status' => $order->status, 'note' => $note, 'user_id' => $by?->id]);

            return $return;
        });
    }
}
