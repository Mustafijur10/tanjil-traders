<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tells people about order changes, after the database change is saved:
 *   - the customer by SMS (placed, confirmed, shipped, delivered, cancelled; each can be turned off)
 *   - the shop by SMS and/or email when a new order arrives
 * Messages are templates from Settings › SMS & Notifications.
 */
class OrderNotifier
{
    public const EVENTS = ['placed', 'confirmed', 'shipped', 'delivered', 'cancelled'];

    public function __construct(private SmsService $sms) {}

    public function orderEvent(Order $order, string $event): void
    {
        if (! in_array($event, self::EVENTS, true)) {
            return;
        }
        DB::afterCommit(function () use ($order, $event) {
            try {
                $order->refresh()->loadMissing('courier');
                if ($this->sms->enabled() && Setting::get("sms.on_{$event}")) {
                    $this->sms->send($order->phone, $this->render((string) Setting::get("sms.tpl_{$event}"), $order), "order_{$event}", $order->id);
                }
                if ($event === 'placed') {
                    $this->tellShop($order);
                }
            } catch (Throwable $e) {
                Log::warning('Order notification failed: ' . $e->getMessage());
            }
        });
    }

    private function tellShop(Order $order): void
    {
        $text = "New order {$order->order_number}: " . number_format($order->total) . " Tk, {$order->name}, {$order->phone}, {$order->city}.";

        if (($phone = Setting::get('notify.order_sms')) && $this->sms->enabled()) {
            $this->sms->send($phone, $text, 'shop_new_order', $order->id);
        }
        if ($email = Setting::get('notify.order_email')) {
            $lines = $order->items()->get()->map(fn ($i) => "- {$i->name}" . ($i->variant_label ? " ({$i->variant_label})" : '') . " x {$i->qty} = " . number_format($i->total))->implode("\n");
            Mail::raw($text . "\n\n" . $lines . "\n\nOpen: " . url("/admin/orders/{$order->id}"), function ($m) use ($email, $order) {
                $m->to($email)->subject("New order {$order->order_number}");
            });
        }
    }

    /** {name} {order} {total} {store} {phone} {courier} {tracking} {link} */
    public function render(string $template, Order $order): string
    {
        return strtr($template, [
            '{name}'     => strtok($order->name, ' ') ?: $order->name,
            '{order}'    => $order->order_number,
            '{total}'    => number_format($order->total),
            '{store}'    => Setting::get('store.name'),
            '{phone}'    => Setting::get('store.phone'),
            '{courier}'  => $order->courier?->name ?? '',
            '{tracking}' => $order->tracking_code ?? '',
            '{link}'     => url('/track-order?number=' . $order->order_number),
        ]);
    }
}
