<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\SearchLog;
use App\Services\ProductFilter;
use App\Support\ProductCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    /** Shop, category, brand, search and deals pages all come here. */
    public function index(Request $request)
    {
        $input = $request->validate([
            'category'   => ['nullable', 'string', 'max:160'],
            'brand_page' => ['nullable', 'string', 'max:160'],
            'brand'      => ['nullable', 'string', 'max:500'],
            'q'          => ['nullable', 'string', 'max:100'],
            'min'        => ['nullable', 'numeric', 'min:0'],
            'max'        => ['nullable', 'numeric', 'min:0'],
            'stock'      => ['nullable', 'boolean'],
            'sale'       => ['nullable', 'boolean'],
            'rating'     => ['nullable', 'integer', 'between:1,5'],
            'f'          => ['nullable', 'array'],
            'f.*'        => ['string', 'max:500'],
            'sort'       => ['nullable', 'in:' . implode(',', ProductFilter::SORTS)],
            'per_page'   => ['nullable', 'integer', 'min:12', 'max:48'],
            'page'       => ['nullable', 'integer', 'min:1'],
        ]);

        $filter = new ProductFilter($input);

        if (! empty($input['category']) && ! $filter->category()) {
            return response()->json(['message' => 'Category not found.'], 404);
        }
        if (! empty($input['brand_page']) && ! $filter->brandPage()) {
            return response()->json(['message' => 'Brand not found.'], 404);
        }

        $products = $filter->products((int) ($input['per_page'] ?? 24));

        // Remember what people search for (first page only), for Analytics › Search.
        if (! empty($input['q']) && (int) ($input['page'] ?? 1) === 1) {
            SearchLog::create([
                'query'         => mb_strtolower(trim($input['q'])),
                'results_count' => $products->total(),
                'visitor_id'    => substr((string) $request->header('X-Visitor'), 0, 32) ?: null,
            ]);
        }

        $category = $filter->category();

        return response()->json([
            'products' => $products,
            'facets'   => $filter->facets(),
            'category' => $category ? [
                'name'             => $category->name,
                'slug'             => $category->slug,
                'description'      => $category->description,
                'image'            => $category->image,
                'icon'             => $category->icon,
                'meta_title'       => $category->meta_title,
                'meta_description' => $category->meta_description,
                'parent'           => $category->parent ? $category->parent->only(['name', 'slug']) : null,
            ] : null,
            'brand'    => $filter->brandPage()?->only(['name', 'slug', 'logo', 'description', 'meta_title', 'meta_description']),
        ]);
    }

    /** Live suggestions under the search box. */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']])['q']);
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';

        return response()->json([
            'products'   => ProductCard::collection(ProductCard::query()
                ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like))
                ->orderByDesc('is_featured')->limit(6)->get()),
            'categories' => Category::query()->active()->where('name', 'like', $like)->limit(4)->get(['name', 'slug']),
            'brands'     => Brand::query()->active()->where('name', 'like', $like)->limit(3)->get(['name', 'slug', 'logo']),
        ]);
    }

    public function brands()
    {
        return Brand::query()->active()
            ->withCount(['products' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'slug', 'logo', 'is_featured']);
    }

    public function product(string $slug)
    {
        $product = Product::query()->active()->where('slug', $slug)
            ->with([
                'brand:id,name,slug,logo',
                'category:id,parent_id,name,slug', 'category.parent:id,name,slug',
                'images',
                'specs.attribute:id,name,slug,unit,group,sort_order,is_comparable',
                'specs.attributeValue:id,value,swatch,sort_order',
                'variants' => fn ($q) => $q->where('is_active', true),
                'variants.values:id,attribute_id,value,slug,swatch,sort_order',
            ])
            ->firstOrFail();

        // Specs grouped for the table: [{ group, rows: [{ name, value }] }]
        $specs = $product->specs
            ->sortBy(fn ($s) => [$s->attribute->sort_order ?? 0, $s->attributeValue->sort_order ?? 0])
            ->groupBy('attribute_id')
            ->map(fn ($rows) => [
                'group' => $rows->first()->attribute->group ?: 'General',
                'name'  => $rows->first()->attribute->name,
                'value' => $rows->map(fn ($r) => $r->attributeValue->value ?? $r->value)->filter()->implode(', '),
            ])
            ->groupBy('group')
            ->map(fn ($rows, $group) => ['group' => $group, 'rows' => $rows->values()])
            ->values();

        // Options a customer chooses between (e.g. Storage: 128GB / 256GB), in spec order.
        $options = $product->variants->flatMap->values->groupBy('attribute_id')
            ->map(function ($values, $attrId) {
                $attr = Attribute::find($attrId);

                return [
                    'id'     => (int) $attrId,
                    'name'   => $attr?->name,
                    'sort'   => $attr?->sort_order ?? 0,
                    'values' => $values->unique('id')->sortBy('sort_order')->map->only(['id', 'value', 'swatch'])->values(),
                ];
            })->sortBy('sort')->values();

        $reviews = Review::where('product_id', $product->id)->where('is_approved', true);
        $breakdown = (clone $reviews)->selectRaw('rating, count(*) as n')->groupBy('rating')->pluck('n', 'rating');

        $related = ProductCard::query()
            ->where('products.id', '!=', $product->id)
            ->where(fn ($q) => $q->where('category_id', $product->category_id)->orWhere('brand_id', $product->brand_id))
            ->orderByRaw('category_id = ? desc', [$product->category_id])
            ->orderByDesc('is_featured')->limit(10)->get();

        return response()->json([
            'product' => [
                'id'                => $product->id,
                'name'              => $product->name,
                'slug'              => $product->slug,
                'sku'               => $product->sku,
                'brand'             => $product->brand,
                'category'          => $product->category,
                'short_description' => $product->short_description,
                'description'       => $product->description,
                'price'             => $product->price,
                'sale_price'        => $product->onSale() ? $product->sale_price : null,
                'final_price'       => $product->final_price,
                'sale_ends_at'      => $product->onSale() ? $product->sale_ends_at?->toIso8601String() : null,
                'stock_qty'         => $product->stock_qty,
                'warranty'          => $product->warranty,
                'rating'            => $product->rating_avg,
                'reviews'           => $product->review_count,
                'images'            => $product->images->map->only(['path', 'alt'])->values(),
                'meta_title'        => $product->meta_title,
                'meta_description'  => $product->meta_description,
                'specs'             => $specs,
                'options'           => $options,
                'variants'          => $product->variants->map(fn ($v) => [
                    'id'          => $v->id,
                    'sku'         => $v->sku,
                    'price'       => $v->price,
                    'final_price' => $v->sale_price !== null && $v->sale_price < $v->price ? $v->sale_price : $v->price,
                    'stock_qty'   => $v->stock_qty,
                    'image'       => $v->image,
                    'value_ids'   => $v->values->pluck('id')->sort()->values(),
                    'label'       => $v->label(),
                ])->values(),
            ],
            'reviews'   => [
                'items'     => (clone $reviews)->latest()->limit(20)->get(['id', 'name', 'rating', 'comment', 'reply', 'created_at']),
                'breakdown' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($r) => [$r => (int) ($breakdown[$r] ?? 0)]),
            ],
            'related'   => ProductCard::collection($related),
        ]);
    }

    /** Customers leave a review; it shows after the shop approves it. */
    public function review(Request $request, string $slug)
    {
        $product = Product::query()->active()->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:80'],
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'max:0'],
        ]);

        Review::create([
            'product_id'  => $product->id,
            'user_id'     => $request->user('sanctum')?->id,
            'name'        => $data['name'],
            'rating'      => $data['rating'],
            'comment'     => $data['comment'] ?? null,
            'is_approved' => false,
        ]);

        return response()->json(['message' => 'Thank you! Your review will appear after we check it.'], 201);
    }

    /** Side-by-side data for 2–4 products. */
    public function compare(Request $request)
    {
        $ids = collect(explode(',', (string) $request->validate(['ids' => ['required', 'string', 'max:100']])['ids']))
            ->map(fn ($id) => (int) $id)->filter()->unique()->take(4)->values();

        $products = ProductCard::query()->whereIn('products.id', $ids)
            ->with(['category:id,name,slug', 'specs.attribute:id,name,group,unit,sort_order,is_comparable', 'specs.attributeValue:id,value'])
            ->get()->sortBy(fn ($p) => $ids->search($p->id))->values();

        // Every comparable spec any of the products has, in order, grouped.
        $attributes = $products->flatMap->specs->pluck('attribute')->filter(fn ($a) => $a && $a->is_comparable)
            ->unique('id')->sortBy('sort_order');

        $rows = $attributes->map(fn ($a) => [
            'group'  => $a->group ?: 'General',
            'name'   => $a->name,
            'values' => $products->map(fn ($p) => $p->specs->where('attribute_id', $a->id)
                ->map(fn ($s) => $s->attributeValue->value ?? $s->value)->filter()->implode(', ') ?: null)->values(),
        ])->values();

        return response()->json([
            'products'   => $products->map(fn ($p) => ProductCard::make($p) + [
                'category' => $p->category?->only(['name', 'slug']),
                'warranty' => $p->warranty,
            ])->values(),
            'rows'       => $rows,
            'categories' => $products->pluck('category.name')->filter()->unique()->values(),
        ]);
    }
}
