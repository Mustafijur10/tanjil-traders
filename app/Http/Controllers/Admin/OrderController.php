<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Store\CheckoutController;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request)
    {
        $request->validate([
            'status'         => ['nullable', Rule::in([...Order::STATUSES, 'all', 'to_ship'])],
            'payment_status' => ['nullable', Rule::in(Order::PAYMENT_STATUSES)],
            'payment_method' => ['nullable', Rule::in(Order::PAYMENT_METHODS)],
            'courier_id'     => ['nullable', 'integer'],
            'source'         => ['nullable', 'in:web,admin'],
            'from'           => ['nullable', 'date'],
            'to'             => ['nullable', 'date'],
            'search'         => ['nullable', 'string', 'max:120'],
            'sort'           => ['nullable', 'in:created_at,total,order_number,name'],
            'dir'            => ['nullable', 'in:asc,desc'],
            'per_page'       => ['nullable', 'integer', 'between:5,100'],
        ]);

        $base = Order::query()
            ->when($request->payment_status, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($request->payment_method, fn ($q, $v) => $q->where('payment_method', $v))
            ->when($request->courier_id, fn ($q, $v) => $q->where('courier_id', $v))
            ->when($request->source, fn ($q, $v) => $q->where('source', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('order_number', 'like', "%{$s}%")
                ->orWhere('name', 'like', "%{$s}%")
                ->orWhere('phone', 'like', '%' . preg_replace('/\D/', '', $s) . '%')
                ->orWhere('tracking_code', 'like', "%{$s}%")));

        $status = $request->status ?: 'all';
        $items = (clone $base)
            ->when($status === 'to_ship', fn ($q) => $q->whereIn('status', ['confirmed', 'processing']))
            ->when(! in_array($status, ['all', 'to_ship'], true), fn ($q) => $q->where('status', $status))
            ->with(['courier:id,name'])
            ->withCount('items')
            ->withSum('items as units', 'qty')
            ->orderBy($request->sort ?: 'created_at', $request->dir ?: 'desc')
            ->orderByDesc('id')
            ->paginate($request->per_page ?: 25);

        $byStatus = (clone $base)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $today = Order::whereDate('created_at', today())->where('status', '!=', 'cancelled');

        return response()->json([
            'items'  => $items,
            'counts' => collect(Order::STATUSES)->mapWithKeys(fn ($s) => [$s => (int) ($byStatus[$s] ?? 0)])
                + ['all' => (int) $byStatus->sum(), 'to_ship' => (int) (($byStatus['confirmed'] ?? 0) + ($byStatus['processing'] ?? 0))],
            'stats'  => [
                'today_orders'  => (clone $today)->count(),
                'today_sales'   => (float) (clone $today)->sum('total'),
                'pending'       => Order::where('status', 'pending')->count(),
                'to_ship'       => Order::whereIn('status', ['confirmed', 'processing'])->count(),
                'unpaid_value'  => (float) Order::whereIn('status', Order::OPEN)->where('payment_status', 'unpaid')->sum('total'),
            ],
            'couriers' => Courier::orderBy('sort_order')->get(['id', 'name', 'is_active']),
        ]);
    }

    public function show(Order $order, ReturnService $returns)
    {
        $order->load([
            'items.product:id,slug,status', 'items.product.images', 'items.variant:id,stock_qty',
            'history.user:id,name', 'shippingMethod', 'courier', 'returns.items',
            'user' => fn ($q) => $q->withCount('orders'),
        ]);

        $cost = $order->items->sum(fn ($i) => ($i->cost_price ?? 0) * $i->qty);
        $hasCost = $order->items->every(fn ($i) => $i->cost_price !== null);

        return response()->json([
            // array_merge, not +, so these replace the plain relation data
            'order'      => array_merge($order->toArray(), [
                'items' => $order->items->map(fn ($i) => array_merge($i->makeVisible('cost_price')->toArray(), [
                    'image'   => $i->product?->images->first()?->path,
                    'product' => $i->product?->only(['id', 'slug', 'status']),
                ])),
                'tracking_link' => $order->trackingLink(),
            ]),
            'profit'     => $hasCost ? round($order->subtotal - $order->discount - $cost, 2) : null,
            'next'       => Order::NEXT[$order->status] ?? [],
            'returnable' => $returns->returnable($order),
            'couriers'   => Courier::where('is_active', true)->orWhere('id', $order->courier_id)->orderBy('sort_order')->get(),
            'shipping'   => ShippingMethod::orderBy('sort_order')->get(['id', 'name', 'charge']),
            'customer_stats' => [
                'orders'    => Order::where('phone', $order->phone)->count(),
                'delivered' => Order::where('phone', $order->phone)->where('status', 'delivered')->count(),
                'cancelled' => Order::where('phone', $order->phone)->whereIn('status', ['cancelled', 'returned'])->count(),
            ],
        ]);
    }

    /** Order taken by phone, in the shop, or on Facebook. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'items'              => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.qty'        => ['required', 'integer', 'min:1', 'max:999'],
            'name'               => ['required', 'string', 'max:120'],
            'phone'              => ['required', 'string', CheckoutController::PHONE_RULE],
            'email'              => ['nullable', 'email', 'max:255'],
            'address'            => ['required', 'string', 'max:500'],
            'city'               => ['required', 'string', 'max:80'],
            'note'               => ['nullable', 'string', 'max:1000'],
            'shipping_method_id' => ['nullable', 'integer', 'exists:shipping_methods,id'],
            'shipping_cost'      => ['nullable', 'numeric', 'min:0'],
            'coupon_code'        => ['nullable', 'string', 'max:40'],
            'discount'           => ['nullable', 'numeric', 'min:0'],
            'payment_method'     => ['required', Rule::in(Order::PAYMENT_METHODS)],
            'payment_status'     => ['required', Rule::in(['unpaid', 'paid'])],
            'transaction_id'     => ['nullable', 'string', 'max:60'],
            'confirm'            => ['boolean'],
        ], ['phone.regex' => 'Enter a valid Bangladeshi mobile number, like 01712345678.']);

        // Link the order to a customer account with the same phone, if there is one
        $digits = substr(preg_replace('/\D/', '', $data['phone']), -10);
        $customer = User::where('role', 'customer')->where('phone', 'like', "%{$digits}")->first();

        $order = $this->orders->place(
            $data + ['status' => ! empty($data['confirm']) ? 'confirmed' : 'pending'],
            $customer,
            'admin',
            $request->user(),
        );

        return response()->json(['id' => $order->id, 'number' => $order->order_number], 201);
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status'         => ['sometimes', Rule::in(array_diff(Order::STATUSES, ['returned', 'pending']))],
            'note'           => ['nullable', 'string', 'max:1000'],
            'payment_status' => ['sometimes', Rule::in(Order::PAYMENT_STATUSES)],
            'transaction_id' => ['sometimes', 'nullable', 'string', 'max:60'],
            'courier_id'     => ['sometimes', 'nullable', 'integer', 'exists:couriers,id'],
            'tracking_code'  => ['sometimes', 'nullable', 'string', 'max:60'],
            'name'           => ['sometimes', 'string', 'max:120'],
            'phone'          => ['sometimes', 'string', CheckoutController::PHONE_RULE],
            'email'          => ['sometimes', 'nullable', 'email', 'max:255'],
            'address'        => ['sometimes', 'string', 'max:500'],
            'city'           => ['sometimes', 'string', 'max:80'],
        ], ['phone.regex' => 'Enter a valid Bangladeshi mobile number, like 01712345678.']);

        DB::transaction(function () use ($data, $order, $request) {
            $fields = collect($data)->except(['status', 'note'])->all();
            if ($fields) {
                $order->update($fields);
            }
            if (isset($data['status']) && $data['status'] !== $order->status) {
                $this->orders->changeStatus($order, $data['status'], $data['note'] ?? null, $request->user());
            } elseif (! empty($data['note'])) {
                // A note on its own goes to the timeline under the current status
                $order->history()->create(['status' => $order->status, 'note' => $data['note'], 'user_id' => $request->user()->id]);
            }
        });

        return $this->show($order->fresh(), app(ReturnService::class));
    }

    /** Move several orders at once (e.g. confirm all pending, mark a batch shipped). */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids'        => ['required', 'array', 'min:1', 'max:200'],
            'ids.*'      => ['integer'],
            'status'     => ['required', Rule::in(['confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])],
            'courier_id' => ['nullable', 'integer', 'exists:couriers,id'],
        ]);

        $done = 0;
        $skipped = [];
        foreach (Order::whereIn('id', $data['ids'])->get() as $order) {
            try {
                if (! empty($data['courier_id']) && $data['status'] === 'shipped') {
                    $order->update(['courier_id' => $data['courier_id']]);
                }
                $this->orders->changeStatus($order, $data['status'], null, $request->user());
                $done++;
            } catch (ValidationException) {
                $skipped[] = $order->order_number;
            }
        }

        return response()->json(['done' => $done, 'skipped' => $skipped]);
    }

    /** Product picker for "Create order": name or SKU, with options and stock. */
    public function products(Request $request, OrderService $orders)
    {
        $s = trim((string) $request->query('q'));

        return Product::query()
            ->where('status', 'active')
            ->when($s, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")))
            ->with(['images' => fn ($q) => $q->orderBy('sort_order'), 'variants' => fn ($q) => $q->where('is_active', true), 'variants.values'])
            ->orderBy('name')
            ->limit(15)
            ->get()
            ->map(fn ($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'sku'      => $p->sku,
                'image'    => $p->images->first()?->path,
                'price'    => $p->final_price,
                'stock'    => $p->stock_qty,
                'variants' => $p->variants->map(fn ($v) => [
                    'id'    => $v->id,
                    'label' => $v->label(),
                    'sku'   => $v->sku,
                    'price' => $orders->variantPrice($p, $v),
                    'stock' => $v->stock_qty,
                ])->values(),
            ]);
    }
}
