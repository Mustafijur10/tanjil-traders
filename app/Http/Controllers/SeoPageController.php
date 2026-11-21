<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Serves the Vue app, but with the real title, description, image and structured data
 * already in the HTML for product, category, brand and information pages.
 * Google, Facebook and WhatsApp read these without running JavaScript.
 */
class SeoPageController extends Controller
{
    public function product(string $slug)
    {
        $p = Product::query()->active()->where('slug', $slug)->with(['brand:id,name', 'images', 'category:id,name'])->first();
        if (! $p) {
            return $this->app(['title' => 'Product not found', 'robots' => 'noindex'], 404);
        }

        $description = $p->meta_description ?: Str::limit(preg_replace('/\s+/', ' ', strip_tags(str_replace("\n", ' · ', (string) $p->short_description) ?: (string) $p->description)), 160);
        $image = $p->images->first() ? asset('storage/' . $p->images->first()->path) : null;

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $p->name,
            'description' => $description,
            'sku'         => $p->sku,
            'image'       => $p->images->map(fn ($i) => asset('storage/' . $i->path))->values(),
            'brand'       => $p->brand ? ['@type' => 'Brand', 'name' => $p->brand->name] : null,
            'offers'      => [
                '@type'         => 'Offer',
                'url'           => url('/product/' . $p->slug),
                'priceCurrency' => 'BDT',
                'price'         => number_format($p->final_price, 2, '.', ''),
                'availability'  => $p->stock_qty > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];
        if ($p->review_count > 0) {
            $schema['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $p->rating_avg, 'reviewCount' => $p->review_count];
        }

        return $this->app([
            'title'       => $p->meta_title ?: $p->name,
            'description' => $description,
            'image'       => $image,
            'type'        => 'product',
            'canonical'   => url('/product/' . $p->slug),
            'schema'      => array_filter($schema, fn ($v) => $v !== null),
        ]);
    }

    public function category(string $slug)
    {
        $c = Category::query()->active()->where('slug', $slug)->first();
        if (! $c) {
            return $this->app(['title' => 'Category not found', 'robots' => 'noindex'], 404);
        }

        return $this->app([
            'title'       => $c->meta_title ?: $c->name,
            'description' => $c->meta_description ?: Str::limit(strip_tags((string) $c->description), 160),
            'image'       => $c->image ? asset('storage/' . $c->image) : null,
            'canonical'   => url('/category/' . $c->slug),
        ]);
    }

    public function brand(string $slug)
    {
        $b = Brand::query()->active()->where('slug', $slug)->first();
        if (! $b) {
            return $this->app(['title' => 'Brand not found', 'robots' => 'noindex'], 404);
        }

        return $this->app([
            'title'       => $b->meta_title ?: $b->name,
            'description' => $b->meta_description ?: Str::limit(strip_tags((string) $b->description), 160),
            'image'       => $b->logo ? asset('storage/' . $b->logo) : null,
            'canonical'   => url('/brand/' . $b->slug),
        ]);
    }

    /** /{slug}: an information page, or the app's own 404. */
    public function page(string $slug)
    {
        $page = Page::where('slug', $slug)->where('is_published', true)->first();
        if (! $page) {
            return $this->app(['title' => 'Page not found', 'robots' => 'noindex'], 404);
        }

        return $this->app([
            'title'       => $page->meta_title ?: $page->title,
            'description' => $page->meta_description ?: Str::limit(preg_replace('/\s+/', ' ', strip_tags((string) $page->content)), 160),
            'canonical'   => url('/' . $page->slug),
        ]);
    }

    /** Search results are not indexed (thin, endless combinations). */
    public function noindex()
    {
        return $this->app(['robots' => 'noindex, follow']);
    }

    private function app(array $meta, int $status = 200)
    {
        $store = Setting::get('store.name');
        if (! empty($meta['title']) && $meta['title'] !== $store) {
            $meta['title'] .= ' | ' . $store;
        }

        return response()->view('app', ['meta' => $meta], $status);
    }
}
