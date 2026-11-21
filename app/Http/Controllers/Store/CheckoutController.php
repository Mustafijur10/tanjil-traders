<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Services\OrderService;
use App\Support\OrderView;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    /** Bangladeshi mobile numbers: 01XXXXXXXXX, optionally with +88 / 88 in front. */
    public const PHONE_RULE = 'regex:/^(?:\+?88)?01[3-9]\d{8}$/';

    public function __construct(private OrderService $orders) {}

    /** Live prices and stock for the lines in the browser's cart. */
    public function cart(Request $request)
    {
        $data = $request->validate($this->itemRules(false));
        $cart = $this->orders->priceCart($data['items'] ?? []);

        return response()->json([
            'lines'    => $cart['lines']->map(fn ($l) => collect($l)->except('cost_price')),
            'subtotal' => $cart['subtotal'],
            'issues'   => $this->issues($cart['issues']),
        ]);
    }

    /** Delivery options, payment methods, and the signed-in customer's details to fill the form. */
    public function options(Request $request)
    {
        $user = $request->user('sanctum');
        $last = $user?->orders()->latest()->first(['name', 'phone', 'email', 'address', 'city', 'shipping_method_id']);

        return response()->json([
            'shipping' => ShippingMethod::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'delivery_time', 'charge', 'free_over']),
            'payments' => OrderService::paymentMethods(),
            'guest'    => (bool) Setting::get('checkout.guest_checkout', true),
            'customer' => $user ? [
                'name'               => $last->name ?? $user->name,
                'phone'              => $last->phone ?? $user->phone,
                'email'              => $last->email ?? $user->email,
                'address'            => $last->address ?? '',
                'city'               => $last->city ?? '',
                'shipping_method_id' => $last->shipping_method_id ?? null,
            ] : null,
        ]);
    }

    /** Totals for the order summary (delivery and coupon included). */
    public function quote(Request $request)
    {
        $data = $request->validate($this->itemRules() + [
            'shipping_method_id' => ['nullable', 'integer'],
            'coupon_code'        => ['nullable', 'string', 'max:40'],
        ]);
        $q = $this->orders->quote($data['items'], $data['shipping_method_id'] ?? null, $data['coupon_code'] ?? null);

        return response()->json([
            'subtotal'      => $q['subtotal'],
            'shipping_cost' => $q['shipping_cost'],
            'discount'      => $q['discount'],
            'total'         => $q['total'],
            'coupon'        => $q['coupon']?->code,
            'coupon_error'  => $q['coupon_error'],
            'issues'        => $this->issues($q['issues']),
        ]);
    }

    public function place(Request $request)
    {
        $user = $request->user('sanctum');
        abort_if(! $user && ! Setting::get('checkout.guest_checkout', true), 401, 'Please sign in to place an order.');

        $payments = collect(OrderService::paymentMethods())->pluck('value')->all();
        $hasShipping = ShippingMethod::where('is_active', true)->exists();

        $data = $request->validate($this->itemRules() + [
            'name'               => ['required', 'string', 'max:120'],
            'phone'              => ['required', 'string', self::PHONE_RULE],
            'email'              => ['nullable', 'email', 'max:255'],
            'address'            => ['required', 'string', 'max:500'],
            'city'               => ['required', 'string', 'max:80'],
            'note'               => ['nullable', 'string', 'max:1000'],
            'shipping_method_id' => [$hasShipping ? 'required' : 'nullable', 'integer', Rule::exists('shipping_methods', 'id')->where('is_active', true)],
            'coupon_code'        => ['nullable', 'string', 'max:40'],
            'payment_method'     => ['required', Rule::in($payments)],
            'transaction_id'     => ['nullable', 'required_unless:payment_method,cod', 'string', 'max:60'],
        ], [
            'phone.regex'                    => 'Enter a valid Bangladeshi mobile number, like 01712345678.',
            'transaction_id.required_unless' => 'Enter the transaction ID from your payment message.',
            'shipping_method_id.required'    => 'Choose a delivery option.',
        ]);

        // Credit the order to the traffic source that brought this visitor (Analytics › Traffic)
        $visitor = preg_match('/^[a-z0-9]{16,32}$/i', (string) $request->header('X-Visitor')) ? $request->header('X-Visitor') : null;
        $order = $this->orders->place($data, $user);
        $order->forceFill(['visitor_id' => $visitor, 'traffic_source' => TrackController::sourceFor($visitor)])->saveQuietly();

        return response()->json(['number' => $order->order_number, 'phone' => $order->phone, 'total' => $order->total], 201);
    }

    /** Public order tracking: order number + the phone used for it. */
    public function track(Request $request)
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:20'],
            'phone'  => ['required', 'string', 'max:20'],
        ]);

        $digits = substr(preg_replace('/\D/', '', $data['phone']), -10);
        $order = Order::where('order_number', strtoupper(trim($data['number'])))->first();

        if (! $order || strlen($digits) < 10 || substr(preg_replace('/\D/', '', $order->phone), -10) !== $digits) {
            return response()->json(['message' => 'No order found with this number and phone.'], 404);
        }

        return OrderView::make($order);
    }

    private function issues(array $issues): array
    {
        return collect($issues)->map(fn ($i) => $i + ['message' => $this->orders->issueMessage($i)])->all();
    }

    private function itemRules(bool $required = true): array
    {
        return [
            'items'              => [$required ? 'required' : 'nullable', 'array', 'max:50'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.qty'        => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
