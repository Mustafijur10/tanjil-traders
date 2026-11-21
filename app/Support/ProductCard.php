<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The product data every product card on the website needs, built the same way everywhere
 * (home sections, shop pages, search, related products).
 */
class ProductCard
{
    /** Published products with just what a card needs loaded. */
    public static function query(): Builder
    {
        return Product::query()
            ->active()
            ->with(['brand:id,name,slug', 'images' => fn ($q) => $q->orderBy('sort_order')])
            ->withCount(['variants' => fn ($q) => $q->where('is_active', true)]);
    }

    public static function make(Product $p): array
    {
        $onSale = $p->onSale();

        return [
            'id'           => $p->id,
            'name'         => $p->name,
            'slug'         => $p->slug,
            'image'        => $p->images->first()?->path,
            'image_alt'    => $p->images->first()?->alt ?: $p->name,
            'hover_image'  => $p->images->get(1)?->path,
            'brand'        => $p->brand?->name,
            'price'        => $p->price,
            'final_price'  => $p->final_price,
            'on_sale'      => $onSale,
            'discount_pct' => $onSale && $p->price > 0 ? (int) round(($p->price - $p->sale_price) / $p->price * 100) : 0,
            'sale_ends_at' => $onSale ? $p->sale_ends_at?->toIso8601String() : null,
            'in_stock'     => $p->stock_qty > 0,
            'stock_qty'    => $p->stock_qty,
            'rating'       => $p->rating_avg,
            'reviews'      => $p->review_count,
            'has_options'  => ($p->variants_count ?? 0) > 0,
            'is_featured'  => $p->is_featured,
        ];
    }

    public static function collection(iterable $products): Collection
    {
        return collect($products)->map(fn (Product $p) => self::make($p))->values();
    }
}
