<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;

/** sitemap.xml and robots.txt, built from the live catalog so Google finds every page. */
class SitemapController extends Controller
{
    public function sitemap()
    {
        $urls = collect([['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'daily']])
            ->push(['loc' => url('/deals'), 'priority' => '0.8', 'changefreq' => 'daily'])
            ->push(['loc' => url('/brands'), 'priority' => '0.5'])
            ->concat(Category::active()->get(['slug', 'updated_at'])->map(fn ($c) => ['loc' => url("/category/{$c->slug}"), 'lastmod' => $c->updated_at, 'priority' => '0.8', 'changefreq' => 'daily']))
            ->concat(Brand::active()->get(['slug', 'updated_at'])->map(fn ($b) => ['loc' => url("/brand/{$b->slug}"), 'lastmod' => $b->updated_at, 'priority' => '0.6']))
            ->concat(Product::active()->get(['slug', 'updated_at'])->map(fn ($p) => ['loc' => url("/product/{$p->slug}"), 'lastmod' => $p->updated_at, 'priority' => '0.9', 'changefreq' => 'weekly']))
            ->concat(Page::where('is_published', true)->get(['slug', 'updated_at'])->map(fn ($p) => ['loc' => url("/{$p->slug}"), 'lastmod' => $p->updated_at, 'priority' => '0.4']));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . e($u['loc']) . '</loc>'
                . (! empty($u['lastmod']) ? '<lastmod>' . $u['lastmod']->toAtomString() . '</lastmod>' : '')
                . (! empty($u['changefreq']) ? "<changefreq>{$u['changefreq']}</changefreq>" : '')
                . "<priority>{$u['priority']}</priority></url>\n";
        }

        return response($xml . '</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /api/',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /account',
            'Disallow: /search',
            'Disallow: /compare',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
