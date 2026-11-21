<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\ReturnService;
use App\Support\OrderView;
use App\Support\ProductCard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** The signed-in customer's own orders, returns and wishlist. */
class AccountController extends Controller
{
    public function summary(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'orders'   => $user->orders()->count(),
            'open'     => $user->orders()->whereIn('status', Order::OPEN)->count(),
            'spent'    => (float) $user->orders()->where('status', 'delivered')->sum('total'),
            'wishlist' => $user->wishlist()->count(),
        ]);
    }

    public function orders(Request $request)
    {
        return $request->user()->orders()
            ->with(['items:id,order_id,product_id,name,qty', 'items.product.images'])
            ->latest()
            ->paginate(10)
            ->through(fn ($o) => [
                'number'     => $o->order_number,
                'status'     => $o->status,
                'total'      => $o->total,
                'created_at' => $o->created_at?->toIso8601String(),
                'items'      => $o->items->map(fn ($i) => ['name' => $i->name, 'qty' => $i->qty, 'image' => $i->product?->images->first()?->path]),
            ]);
    }

    public function order(Request $request, string $number)
    {
        return OrderView::make($this->own($request, $number));
    }

    public function cancel(Request $request, string $number, OrderService $orders)
    {
        $order = $this->own($request, $number);
        if (! $order->customerCanCancel()) {
            throw ValidationException::withMessages(['status' => 'This order is already being prepared. Please call us to cancel it.']);
        }
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:300']]);
        $orders->changeStatus($order, 'cancelled', 'Cancelled by the customer.' . (! empty($data['reason']) ? ' Reason: ' . $data['reason'] : ''));

        return OrderView::make($order->fresh());
    }

    public function requestReturn(Request $request, string $number, ReturnService $returns)
    {
        $order = $this->own($request, $number);
        if (! $order->customerCanReturn()) {
            throw ValidationException::withMessages(['items' => 'This order can no longer be returned online. Please contact us.']);
        }
        $data = $request->validate([
            'reason'                => ['required', Rule::in(OrderReturn::REASONS)],
            'details'               => ['nullable', 'string', 'max:1000'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.qty'           => ['required', 'integer', 'min:0'],
        ]);
        $returns->create($order, $data['items'], $data['reason'], $data['details'] ?? null, $request->user());

        return OrderView::make($order->fresh());
    }

    public function wishlist(Request $request)
    {
        $ids = $request->user()->wishlist()->orderByPivot('created_at', 'desc')->pluck('products.id');
        $cards = ProductCard::query()->whereIn('id', $ids)->get()->sortBy(fn ($p) => $ids->search($p->id));

        return response()->json(['ids' => $ids, 'products' => ProductCard::collection($cards)]);
    }

    /** Add or remove one product. */
    public function toggleWishlist(Request $request, Product $product)
    {
        $result = $request->user()->wishlist()->toggle([$product->id]);

        return response()->json(['saved' => ! empty($result['attached']), 'ids' => $request->user()->wishlist()->pluck('products.id')]);
    }

    /** After signing in: add what was saved in this browser while signed out. */
    public function syncWishlist(Request $request)
    {
        $data = $request->validate(['ids' => ['array', 'max:200'], 'ids.*' => ['integer']]);
        $ids = Product::whereIn('id', $data['ids'] ?? [])->pluck('id');
        $request->user()->wishlist()->syncWithoutDetaching($ids);

        return response()->json(['ids' => $request->user()->wishlist()->pluck('products.id')]);
    }

    private function own(Request $request, string $number): Order
    {
        return $request->user()->orders()->where('order_number', $number)->firstOrFail();
    }
}
