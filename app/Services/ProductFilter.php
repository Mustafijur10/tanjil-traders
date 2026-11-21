<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductCard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The one product search used by the shop, category, brand, search and deals pages.
 *
 * Input (all optional):
 *   category  slug (includes its subcategories)     brand  "apple,samsung"
 *   q         words to search                         min / max  price (the price customers pay)
 *   stock     1 = in stock only                       sale  1 = on sale only
 *   rating    4 = 4 stars and up                      f[ram] = "8gb,16gb" (spec slug => value slugs)
 *   sort      featured | newest | price_asc | price_desc | popular | rating
 *
 * Facets are "disjunctive": the counts for a filter ignore that filter's own selection,
 * so picking 8GB still shows how many 16GB products there are.
 */
class ProductFilter
{
    public const SORTS = ['featured', 'newest', 'price_asc', 'price_desc', 'popular', 'rating'];

    private ?Category $category = null;
    private ?Brand $brandPage = null;
    private array $categoryIds = [];
    private array $brandIds = [];
    private array $specs = [];   // attribute_id => [value ids]
    private string $q = '';

    public function __construct(private array $input)
    {
        $this->resolve();
    }

    /** The price a customer pays right now (sale price while the sale runs). */
    public static function priceSql(): string
    {
        return '(CASE WHEN ' . Product::saleLiveSql() . ' THEN products.sale_price ELSE products.price END)';
    }

    public function category(): ?Category
    {
        return $this->category;
    }

    public function brandPage(): ?Brand
    {
        return $this->brandPage;
    }

    public function products(int $perPage = 24)
    {
        $query = $this->apply(ProductCard::query());
        $this->sort($query, $this->input['sort'] ?? null);

        $page = $query->paginate(min(max($perPage, 12), 48))->withQueryString();
        $page->setCollection(ProductCard::collection($page->getCollection()));

        return $page;
    }

    public function facets(): array
    {
        $price = $this->apply(Product::query()->active(), except: 'price')
            ->selectRaw('min(' . self::priceSql() . ') as lo, max(' . self::priceSql() . ') as hi')->first();

        return [
            'price'         => ['min' => (int) floor((float) $price->lo), 'max' => (int) ceil((float) $price->hi)],
            'brands'        => $this->brandFacet(),
            'specs'         => $this->specFacets(),
            'subcategories' => $this->subcategoryFacet(),
            'in_stock'      => $this->apply(Product::query()->active(), except: 'stock')->where('stock_qty', '>', 0)->count(),
            'on_sale'       => $this->apply(Product::query()->active(), except: 'sale')->whereRaw(self::priceSql() . ' < products.price')->count(),
            'ratings'       => collect([4, 3])->mapWithKeys(fn ($r) => [$r => $this->apply(Product::query()->active(), except: 'rating')->where('rating_avg', '>=', $r)->count()]),
        ];
    }

    // ── Building the query ──

    private function resolve(): void
    {
        $i = $this->input;

        if (! empty($i['category'])) {
            $this->category = Category::query()->active()->where('slug', $i['category'])->with(['parent', 'children' => fn ($q) => $q->where('is_active', true)])->first();
            $this->categoryIds = $this->category ? $this->category->selfAndChildIds() : [0];
        }
        if (! empty($i['brand_page'])) {
            $this->brandPage = Brand::query()->active()->where('slug', $i['brand_page'])->first();
        }

        $brandSlugs = array_filter(explode(',', (string) ($i['brand'] ?? '')));
        if ($this->brandPage) {
            $brandSlugs = [$this->brandPage->slug];
        }
        if ($brandSlugs) {
            $this->brandIds = Brand::whereIn('slug', $brandSlugs)->pluck('id')->all() ?: [0];
        }

        foreach ((array) ($i['f'] ?? []) as $attrSlug => $valueSlugs) {
            $attr = Attribute::where('slug', $attrSlug)->first();
            if (! $attr) {
                continue;
            }
            $ids = $attr->values()->whereIn('slug', array_filter(explode(',', (string) $valueSlugs)))->pluck('id')->all();
            if ($ids) {
                $this->specs[$attr->id] = $ids;
            }
        }

        $this->q = trim(mb_substr((string) ($i['q'] ?? ''), 0, 100));
    }

    /** Applies every active filter, except the one named in $except (used for facet counts). */
    private function apply(Builder $query, ?string $except = null, ?int $exceptAttr = null): Builder
    {
        $i = $this->input;

        if ($this->categoryIds && $except !== 'category') {
            $query->whereIn('products.category_id', $this->categoryIds);
        }
        if ($this->brandIds && $except !== 'brand') {
            $query->whereIn('products.brand_id', $this->brandIds);
        }
        if ($this->q !== '') {
            $words = preg_split('/\s+/', $this->q);
            foreach ($words as $word) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $word) . '%';
                $query->where(fn ($w) => $w->where('products.name', 'like', $like)
                    ->orWhere('products.sku', 'like', $like)
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like)));
            }
        }
        if ($except !== 'price') {
            if (is_numeric($i['min'] ?? null)) {
                // "? + 0" makes the comparison numeric on every database (SQLite would compare as text).
                $query->whereRaw(self::priceSql() . ' >= (? + 0)', [(float) $i['min']]);
            }
            if (is_numeric($i['max'] ?? null)) {
                $query->whereRaw(self::priceSql() . ' <= (? + 0)', [(float) $i['max']]);
            }
        }
        if (! empty($i['stock']) && $except !== 'stock') {
            $query->where('products.stock_qty', '>', 0);
        }
        if (! empty($i['sale']) && $except !== 'sale') {
            $query->whereRaw(self::priceSql() . ' < products.price');
        }
        if (is_numeric($i['rating'] ?? null) && $except !== 'rating') {
            $query->where('products.rating_avg', '>=', (float) $i['rating']);
        }
        // A product must match every chosen spec, and any of the values chosen within one spec.
        foreach ($this->specs as $attrId => $valueIds) {
            if ($except === 'spec' && $exceptAttr === $attrId) {
                continue;
            }
            $query->whereExists(fn ($s) => $s->from('product_specs')
                ->whereColumn('product_specs.product_id', 'products.id')
                ->whereIn('product_specs.attribute_value_id', $valueIds));
        }

        return $query;
    }

    private function sort(Builder $query, ?string $sort): void
    {
        match ($sort) {
            'newest'     => $query->latest('products.created_at'),
            'price_asc'  => $query->orderByRaw(self::priceSql() . ' asc'),
            'price_desc' => $query->orderByRaw(self::priceSql() . ' desc'),
            'rating'     => $query->orderByDesc('products.rating_avg')->orderByDesc('products.review_count'),
            'popular'    => $query->orderByRaw('(select coalesce(sum(qty), 0) from order_items where order_items.product_id = products.id) desc')->orderByDesc('products.review_count'),
            default      => $query->orderByDesc('products.is_featured')->orderByRaw('products.stock_qty > 0 desc')->latest('products.created_at'),
        };
        $query->orderBy('products.id');
    }

    // ── Facets ──

    private function brandFacet(): array
    {
        $counts = $this->apply(Product::query()->active(), except: 'brand')
            ->whereNotNull('brand_id')->groupBy('brand_id')->selectRaw('brand_id, count(*) as n')->pluck('n', 'brand_id');

        return Brand::query()->active()->whereIn('id', $counts->keys())->orderBy('name')->get(['id', 'name', 'slug', 'logo'])
            ->map(fn ($b) => ['slug' => $b->slug, 'name' => $b->name, 'count' => (int) $counts[$b->id]])
            ->sortByDesc('count')->values()->all();
    }

    private function specFacets(): array
    {
        // Specs to offer: those of the category (and its parent), or, without a category, those used by the results.
        if ($this->category) {
            $ids = $this->category->attributes()->pluck('attributes.id')
                ->merge($this->category->parent?->attributes()->pluck('attributes.id') ?? [])
                ->merge($this->category->children->flatMap(fn ($c) => $c->attributes()->pluck('attributes.id')))
                ->unique();
            $attributes = Attribute::whereIn('id', $ids)->where('is_filterable', true)->where('type', 'select')->orderBy('sort_order')->with('values')->get();
        } else {
            $used = DB::table('product_specs')->whereIn('product_id', $this->apply(Product::query()->active())->select('products.id'))
                ->distinct()->pluck('attribute_id');
            $attributes = Attribute::whereIn('id', $used)->where('is_filterable', true)->where('type', 'select')->orderBy('sort_order')->with('values')->get();
        }

        $out = [];
        foreach ($attributes as $attr) {
            $productIds = $this->apply(Product::query()->active(), except: 'spec', exceptAttr: $attr->id)->select('products.id');
            $counts = DB::table('product_specs')->where('attribute_id', $attr->id)->whereIn('product_id', $productIds)
                ->groupBy('attribute_value_id')->selectRaw('attribute_value_id, count(distinct product_id) as n')->pluck('n', 'attribute_value_id');

            $values = $attr->values->filter(fn ($v) => ($counts[$v->id] ?? 0) > 0 || in_array($v->id, $this->specs[$attr->id] ?? [], true))
                ->map(fn ($v) => ['slug' => $v->slug, 'value' => $v->value, 'swatch' => $v->swatch, 'count' => (int) ($counts[$v->id] ?? 0)])
                ->values();

            if ($values->count() > 0) {
                $out[] = ['slug' => $attr->slug, 'name' => $attr->name, 'unit' => $attr->unit, 'group' => $attr->group, 'values' => $values];
            }
        }

        return $out;
    }

    private function subcategoryFacet(): array
    {
        if (! $this->category || $this->category->children->isEmpty()) {
            return [];
        }
        $counts = $this->apply(Product::query()->active(), except: 'category')
            ->whereIn('category_id', $this->category->children->pluck('id'))
            ->groupBy('category_id')->selectRaw('category_id, count(*) as n')->pluck('n', 'category_id');

        return $this->category->children->map(fn ($c) => ['slug' => $c->slug, 'name' => $c->name, 'count' => (int) ($counts[$c->id] ?? 0)])->values()->all();
    }
}
