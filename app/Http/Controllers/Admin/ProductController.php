<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\ProductSaver;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(private ProductSaver $saver)
    {
    }

    public function index(Request $request)
    {
        $request->validate([
            'search'      => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer'],
            'brand_id'    => ['nullable', 'integer'],
            'status'      => ['nullable', 'in:active,draft'],
            'stock'       => ['nullable', 'in:in,low,out'],
            'sort'        => ['nullable', 'in:name,price,stock_qty,updated_at,created_at'],
            'dir'         => ['nullable', 'in:asc,desc'],
            'per_page'    => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = $this->filtered($request)
            ->with(['category:id,name,parent_id', 'brand:id,name', 'images' => fn ($q) => $q->orderBy('sort_order')])
            ->withCount('variants')
            ->orderBy($request->input('sort', 'updated_at'), $request->input('dir', 'desc'));

        $page = $query->paginate($request->integer('per_page', 25));
        $page->getCollection()->transform(function (Product $p) {
            $p->setAttribute('image', $p->images->first()?->path);
            $p->unsetRelation('images');

            return $p;
        });

        return response()->json([
            'items'  => $page,
            'counts' => $this->counts(),
        ]);
    }

    /** Everything the product form needs: categories, brands and every spec with its values. */
    public function formData()
    {
        return [
            'categories' => Category::query()->with('attributes:id')->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'parent_id', 'name', 'is_active'])
                ->map(fn ($c) => [
                    'id' => $c->id, 'parent_id' => $c->parent_id, 'name' => $c->name, 'is_active' => $c->is_active,
                    'attribute_ids' => $c->attributes->pluck('id'),
                ]),
            'brands'     => Brand::query()->orderBy('name')->get(['id', 'name']),
            'attributes' => Attribute::query()->with('values')->orderBy('sort_order')->get(),
            'low_stock'  => (int) Setting::get('inventory.low_stock_threshold', 5),
        ];
    }

    public function show(Product $product)
    {
        $product->load(['images', 'specs', 'variants.values:id,attribute_id,value']);
        $product->makeVisible('cost_price');

        return $product;
    }

    public function store(Request $request)
    {
        $product = $this->saver->save($this->validated($request));

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        return $this->saver->save($this->validated($request, $product), $product);
    }

    /** Old orders keep the product's name and price, so deleting is safe. */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->noContent();
    }

    public function duplicate(Product $product)
    {
        $copy = DB::transaction(function () use ($product) {
            $product->load(['images', 'specs', 'variants.values']);

            $copy = $product->replicate(['slug', 'sku', 'rating_avg', 'review_count']);
            $copy->name = $product->name . ' (copy)';
            $copy->slug = Slug::unique(Product::class, null, $copy->name);
            $copy->status = 'draft';
            $copy->stock_qty = 0;
            $copy->save();

            $copy->images()->createMany($product->images->map->only(['path', 'alt', 'sort_order'])->all());
            $copy->specs()->createMany($product->specs->map->only(['attribute_id', 'attribute_value_id', 'value'])->all());
            foreach ($product->variants as $v) {
                $nv = $copy->variants()->create($v->only(['price', 'sale_price', 'image', 'is_active']) + ['stock_qty' => 0]);
                $nv->values()->sync($v->values->pluck('id'));
            }

            return $copy;
        });

        return response()->json($copy, 201);
    }

    /** Body: { ids: [...], action: active | draft | feature | unfeature | delete } */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids'    => ['required', 'array', 'max:500'],
            'ids.*'  => ['integer'],
            'action' => ['required', 'in:active,draft,feature,unfeature,delete'],
        ]);

        $query = Product::whereIn('id', $data['ids']);
        $count = match ($data['action']) {
            'active', 'draft' => $query->update(['status' => $data['action']]),
            'feature'         => $query->update(['is_featured' => true]),
            'unfeature'       => $query->update(['is_featured' => false]),
            'delete'          => $query->get()->each->delete()->count(),
        };

        return response()->json(['message' => "{$count} products updated.", 'count' => $count]);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $variantIds = $product ? $product->variants()->pluck('id')->all() : [];

        return $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'slug'              => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'sku'               => ['nullable', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($product?->id)],
            'category_id'       => ['required', 'integer', 'exists:categories,id'],
            'brand_id'          => ['nullable', 'integer', 'exists:brands,id'],
            'short_description' => ['nullable', 'string', 'max:2000'],
            'description'       => ['nullable', 'string', 'max:100000'],
            'price'             => ['required', 'numeric', 'min:0', 'max:99999999'],
            'sale_price'        => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'sale_starts_at'    => ['nullable', 'date'],
            'sale_ends_at'      => ['nullable', 'date', Rule::when($request->filled('sale_starts_at'), 'after:sale_starts_at')],
            'cost_price'        => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'stock_qty'         => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'warranty'          => ['nullable', 'string', 'max:120'],
            'is_featured'       => ['boolean'],
            'status'            => ['required', 'in:active,draft'],
            'meta_title'        => ['nullable', 'string', 'max:255'],
            'meta_description'  => ['nullable', 'string', 'max:300'],

            'images'            => ['array', 'max:12'],
            'images.*.path'     => ['required', 'string', 'max:255'],
            'images.*.alt'      => ['nullable', 'string', 'max:255'],

            'specs'                => ['array'],
            'specs.*.attribute_id' => ['required', 'integer', 'exists:attributes,id'],
            'specs.*.value_ids'    => ['array'],
            'specs.*.value_ids.*'  => ['integer'],
            'specs.*.value'        => ['nullable', 'string', 'max:255'],

            'variants'              => ['array', 'max:100'],
            'variants.*.id'         => ['nullable', 'integer', Rule::in($variantIds)],
            'variants.*.sku'        => ['nullable', 'string', 'max:60', 'distinct', Rule::unique('product_variants', 'sku')->whereNotIn('id', $variantIds ?: [0])],
            'variants.*.price'      => ['required', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_qty'  => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'variants.*.image'      => ['nullable', 'string', 'max:255'],
            'variants.*.is_active'  => ['boolean'],
            'variants.*.value_ids'  => ['required', 'array', 'min:1'],
            'variants.*.value_ids.*'=> ['integer', 'exists:attribute_values,id'],
        ], [
            'sale_price.lt'           => 'The sale price must be lower than the regular price.',
            'variants.*.sku.distinct' => 'Two variants have the same SKU.',
        ]);
    }

    private function filtered(Request $request): Builder
    {
        $low = (int) Setting::get('inventory.low_stock_threshold', 5);

        return Product::query()
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")))
            ->when($request->category_id, function ($q, $id) {
                $category = Category::find($id);
                $q->whereIn('category_id', $category ? $category->selfAndChildIds() : [0]);
            })
            ->when($request->brand_id, fn ($q, $id) => $q->where('brand_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->stock === 'out', fn ($q) => $q->where('stock_qty', '<=', 0))
            ->when($request->stock === 'low', fn ($q) => $q->whereBetween('stock_qty', [1, $low]))
            ->when($request->stock === 'in', fn ($q) => $q->where('stock_qty', '>', 0));
    }

    private function counts(): array
    {
        $low = (int) Setting::get('inventory.low_stock_threshold', 5);
        $row = Product::query()->selectRaw(
            "count(*) as total,
             sum(case when status = 'active' then 1 else 0 end) as active,
             sum(case when status = 'draft' then 1 else 0 end) as draft,
             sum(case when stock_qty between 1 and ? then 1 else 0 end) as low,
             sum(case when stock_qty <= 0 then 1 else 0 end) as out_of_stock,
             sum(case when stock_qty > 0 then stock_qty * coalesce(cost_price, price) else 0 end) as stock_value",
            [$low]
        )->first();

        return [
            'all'         => (int) $row->total,
            'active'      => (int) $row->active,
            'draft'       => (int) $row->draft,
            'low'         => (int) $row->low,
            'out'         => (int) $row->out_of_stock,
            'stock_value' => (float) $row->stock_value, // at cost price where known
        ];
    }
}
