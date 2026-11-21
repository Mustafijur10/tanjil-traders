<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Support\ProductCard;
use Illuminate\Support\Facades\DB;

/**
 * Everything on the home page in one request: carousel, promo banners and every active section
 * in the order the admin arranged them. Sections with nothing to show are left out.
 */
class HomeController extends Controller
{
    public function __invoke()
    {
        $sections = HomeSection::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->map(fn (HomeSection $s) => $this->resolve($s))
            ->filter(fn ($s) => collect($s['items'])->isNotEmpty())
            ->values();

        return response()->json([
            'hero'     => Banner::query()->where('type', 'hero')->live()->get(),
            'sections' => $sections,
        ]);
    }

    private function resolve(HomeSection $s): array
    {
        $limit = $s->item_limit;
        $products = fn ($q) => ProductCard::collection($q->limit($limit)->get());

        $items = match ($s->type) {
            'featured'     => $products(ProductCard::query()->where('is_featured', true)->latest()),
            'new'          => $products(ProductCard::query()->latest()),
            'deals'        => $products(ProductCard::query()->onSaleNow()
                ->orderByRaw('sale_ends_at is null')->orderBy('sale_ends_at')),
            'best_sellers' => $this->bestSellers($limit),
            'category'     => $s->category
                ? $products(ProductCard::query()->whereIn('category_id', $s->category->selfAndChildIds())->latest())
                : collect(),
            'brand'        => $products(ProductCard::query()->where('brand_id', $s->brand_id)->latest()),
            'categories'   => Category::query()->active()->where('is_featured', true)->orderBy('sort_order')->limit($limit)
                ->get(['id', 'parent_id', 'name', 'slug', 'icon', 'image']),
            'brands'       => Brand::query()->active()->where('is_featured', true)->orderBy('sort_order')->limit($limit)
                ->get(['id', 'name', 'slug', 'logo']),
            'promo'        => Banner::query()->where('type', 'promo')->live()->limit($limit)->get(),
            default        => collect(),
        };

        return [
            'id'       => $s->id,
            'type'     => $s->type,
            'title'    => $s->title,
            'subtitle' => $s->subtitle,
            // Where "View all" goes
            'link'     => match ($s->type) {
                'category' => $s->category ? '/category/' . $s->category->slug : null,
                'brand'    => $s->brand ? '/brand/' . $s->brand->slug : null,
                'deals'    => '/deals',
                'brands'   => '/brands',
                'new', 'featured', 'best_sellers' => '/shop?sort=' . ['new' => 'newest', 'featured' => 'featured', 'best_sellers' => 'popular'][$s->type],
                default    => null,
            },
            'items'    => $items,
        ];
    }

    /** Most units sold in the last 90 days; falls back to the best-rated products while there are no orders yet. */
    private function bestSellers(int $limit)
    {
        $ids = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->where('orders.created_at', '>=', now()->subDays(90))
            ->whereNotNull('order_items.product_id')
            ->groupBy('order_items.product_id')
            ->orderByRaw('sum(order_items.qty) desc')
            ->limit($limit)
            ->pluck('order_items.product_id');

        $query = ProductCard::query();
        if ($ids->isNotEmpty()) {
            $products = $query->whereIn('id', $ids)->get()->sortBy(fn (Product $p) => $ids->search($p->id));
        } else {
            $products = $query->orderByDesc('review_count')->orderByDesc('rating_avg')->limit($limit)->get();
        }

        return ProductCard::collection($products);
    }
}
