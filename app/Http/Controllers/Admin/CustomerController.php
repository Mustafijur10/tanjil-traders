<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

/** Registered customer accounts, with what they have bought. */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search'   => ['nullable', 'string', 'max:120'],
            'status'   => ['nullable', 'in:all,active,disabled,buyers,new'],
            'sort'     => ['nullable', 'in:name,created_at,orders_count,spent,last_order_at'],
            'dir'      => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'between:5,100'],
        ]);

        $base = User::where('role', 'customer')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")));

        $status = $request->status ?: 'all';
        $items = (clone $base)
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'disabled', fn ($q) => $q->where('is_active', false))
            ->when($status === 'buyers', fn ($q) => $q->whereHas('orders', fn ($o) => $o->where('status', 'delivered')))
            ->when($status === 'new', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->withCount('orders')
            ->withSum(['orders as spent' => fn ($q) => $q->where('status', 'delivered')], 'total')
            ->withMax('orders as last_order_at', 'created_at')
            ->orderBy($request->sort ?: 'created_at', $request->dir ?: 'desc')
            ->paginate($request->per_page ?: 25);

        $items->getCollection()->transform(fn ($u) => $u->setAttribute('spent', (float) $u->spent));

        return response()->json([
            'items'  => $items,
            'counts' => [
                'all'      => (clone $base)->count(),
                'active'   => (clone $base)->where('is_active', true)->count(),
                'disabled' => (clone $base)->where('is_active', false)->count(),
                'buyers'   => (clone $base)->whereHas('orders', fn ($o) => $o->where('status', 'delivered'))->count(),
                'new'      => (clone $base)->where('created_at', '>=', now()->subDays(30))->count(),
            ],
            'stats'  => [
                'guest_orders' => Order::whereNull('user_id')->count(),
                'repeat'       => User::where('role', 'customer')->has('orders', '>=', 2)->count(),
            ],
        ]);
    }

    public function show(User $customer)
    {
        abort_unless($customer->role === 'customer', 404);

        $orders = $customer->orders()->withCount('items')->latest()->get(['id', 'order_number', 'status', 'total', 'payment_status', 'created_at']);
        $delivered = $orders->where('status', 'delivered');

        return response()->json([
            'customer' => $customer,
            'stats'    => [
                'orders'    => $orders->count(),
                'spent'     => (float) $delivered->sum('total'),
                'average'   => $delivered->count() ? round($delivered->avg('total'), 2) : 0,
                'cancelled' => $orders->whereIn('status', ['cancelled', 'returned'])->count(),
                'wishlist'  => $customer->wishlist()->count(),
                'reviews'   => $customer->reviews()->count(),
                'first_order_at' => $orders->min('created_at'),
            ],
            'orders'   => $orders,
            'address'  => $customer->orders()->latest()->first(['address', 'city']),
        ]);
    }

    /** Disable or re-enable sign-in. Disabled customers are signed out everywhere. */
    public function update(Request $request, User $customer)
    {
        abort_unless($customer->role === 'customer', 404);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $customer->update($data);
        if (! $data['is_active']) {
            $customer->tokens()->delete();
        }

        return $customer;
    }
}
