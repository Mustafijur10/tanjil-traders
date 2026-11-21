<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    /** Stock per product, with each option's stock for products that have options. */
    public function index(Request $request)
    {
        $request->validate([
            'status'      => ['nullable', 'in:all,in,low,out'],
            'search'      => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer'],
            'sort'        => ['nullable', 'in:name,stock_qty,value'],
            'dir'         => ['nullable', 'in:asc,desc'],
            'per_page'    => ['nullable', 'integer', 'between:5,100'],
        ]);

        $low = (int) Setting::get('inventory.low_stock_threshold', 5);
        $base = Product::query()
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")
                ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', "%{$s}%"))))
            ->when($request->category_id, fn ($q, $c) => $q->where(fn ($w) => $w->where('category_id', $c)
                ->orWhereIn('category_id', fn ($sub) => $sub->select('id')->from('categories')->where('parent_id', $c))));

        $status = $request->status ?: 'all';
        $sort = $request->sort ?: 'stock_qty';
        $items = (clone $base)
            ->when($status === 'in', fn ($q) => $q->where('stock_qty', '>', $low))
            ->when($status === 'low', fn ($q) => $q->whereBetween('stock_qty', [1, $low]))
            ->when($status === 'out', fn ($q) => $q->where('stock_qty', '<=', 0))
            ->with(['images' => fn ($q) => $q->orderBy('sort_order'), 'category:id,name', 'variants.values'])
            ->when($sort === 'value', fn ($q) => $q->orderByRaw('stock_qty * coalesce(cost_price, price) ' . ($request->dir === 'asc' ? 'asc' : 'desc')))
            ->when($sort !== 'value', fn ($q) => $q->orderBy($sort, $request->dir ?: 'asc'))
            ->orderBy('id')
            ->paginate($request->per_page ?: 25);

        $items->getCollection()->transform(fn ($p) => [
            'id'         => $p->id,
            'name'       => $p->name,
            'sku'        => $p->sku,
            'status'     => $p->status,
            'image'      => $p->images->first()?->path,
            'category'   => $p->category?->name,
            'stock_qty'  => $p->stock_qty,
            'price'      => $p->price,
            'cost_price' => $p->cost_price,
            'value'      => round($p->stock_qty * ($p->cost_price ?? $p->price ?? 0), 2),
            'variants'   => $p->variants->map(fn ($v) => [
                'id' => $v->id, 'label' => $v->label(), 'sku' => $v->sku, 'stock_qty' => $v->stock_qty, 'is_active' => $v->is_active,
            ])->values(),
        ]);

        $all = Product::query();

        return response()->json([
            'items'  => $items,
            'low'    => $low,
            'counts' => [
                'all' => (clone $base)->count(),
                'in'  => (clone $base)->where('stock_qty', '>', $low)->count(),
                'low' => (clone $base)->whereBetween('stock_qty', [1, $low])->count(),
                'out' => (clone $base)->where('stock_qty', '<=', 0)->count(),
            ],
            'stats'  => [
                'units'        => (int) (clone $all)->where('stock_qty', '>', 0)->sum('stock_qty'),
                'cost_value'   => (float) (clone $all)->where('stock_qty', '>', 0)->selectRaw('sum(stock_qty * coalesce(cost_price, 0)) as v')->value('v'),
                'retail_value' => (float) (clone $all)->where('stock_qty', '>', 0)->selectRaw('sum(stock_qty * coalesce(sale_price, price)) as v')->value('v'),
                'missing_cost' => (clone $all)->where('stock_qty', '>', 0)->whereNull('cost_price')->count(),
            ],
        ]);
    }

    /** Add stock that arrived, take out damaged/lost units, or set the counted amount. */
    public function adjust(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer'],
            'mode'       => ['required', 'in:add,remove,set'],
            'qty'        => ['required', 'integer', 'min:0', 'max:100000'],
            'reason'     => ['required', Rule::in(['restock', 'adjustment'])],
            'note'       => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $variant = null;
        if (! empty($data['variant_id'])) {
            $variant = ProductVariant::where('product_id', $product->id)->findOrFail($data['variant_id']);
        } elseif ($product->variants()->exists()) {
            throw ValidationException::withMessages(['variant_id' => 'Choose which option to adjust.']);
        }

        $current = ($variant ?? $product)->stock_qty;
        $change = match ($data['mode']) {
            'add'    => $data['qty'],
            'remove' => -$data['qty'],
            'set'    => $data['qty'] - $current,
        };
        if ($change === 0) {
            throw ValidationException::withMessages(['qty' => 'Nothing to change.']);
        }
        if ($current + $change < 0) {
            throw ValidationException::withMessages(['qty' => "Only {$current} in stock."]);
        }

        $movement = StockMovement::record($product, $variant, $change, $data['reason'], ['note' => $data['note'] ?? null, 'user_id' => $request->user()->id]);

        return response()->json(['movement' => $movement, 'stock_qty' => $product->fresh()->stock_qty]);
    }

    public function movements(Request $request)
    {
        $request->validate([
            'reason'     => ['nullable', Rule::in(StockMovement::REASONS)],
            'product_id' => ['nullable', 'integer'],
            'search'     => ['nullable', 'string', 'max:120'],
            'from'       => ['nullable', 'date'],
            'to'         => ['nullable', 'date'],
            'per_page'   => ['nullable', 'integer', 'between:5,100'],
        ]);

        $base = StockMovement::query()
            ->when($request->product_id, fn ($q, $v) => $q->where('product_id', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($request->search, fn ($q, $s) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")));

        $items = (clone $base)
            ->when($request->reason, fn ($q, $v) => $q->where('reason', $v))
            ->with(['product:id,name,sku', 'variant.values', 'user:id,name'])
            ->latest('created_at')->latest('id')
            ->paginate($request->per_page ?: 25);

        $orderNumbers = \App\Models\Order::whereIn('id', $items->pluck('order_id')->filter())->pluck('order_number', 'id');
        $items->getCollection()->transform(fn ($m) => $m->toArray() + [
            'variant_label' => $m->variant?->label(),
            'order_number'  => $m->order_id ? ($orderNumbers[$m->order_id] ?? null) : null,
        ]);

        $sums = (clone $base)->selectRaw('reason, sum(`change`) as units, count(*) as n')->groupBy('reason')->get()->keyBy('reason');

        return response()->json([
            'items'  => $items,
            'counts' => collect(StockMovement::REASONS)->mapWithKeys(fn ($r) => [$r => (int) ($sums[$r]->n ?? 0)])->all() + ['all' => (int) $sums->sum('n')],
            'units'  => collect(StockMovement::REASONS)->mapWithKeys(fn ($r) => [$r => (int) ($sums[$r]->units ?? 0)]),
        ]);
    }
}
