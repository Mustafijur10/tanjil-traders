<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Translation;

/**
 * Everything every page needs once on load: public settings, the Bangla dictionary,
 * the category menu, footer pages and the announcement bar.
 */
class BootstrapController extends Controller
{
    public function __invoke()
    {
        $categories = Category::query()->active()->where('show_in_nav', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug', 'icon', 'image']);
        $children = $categories->whereNotNull('parent_id')->groupBy('parent_id');

        return response()->json([
            'settings'      => Setting::publicValues(),
            'translations'  => ['bn' => (object) Translation::dictionary()],
            'menu'          => $categories->whereNull('parent_id')->values()->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'slug' => $c->slug, 'icon' => $c->icon, 'image' => $c->image,
                'children' => ($children[$c->id] ?? collect())->map->only(['id', 'name', 'slug'])->values(),
            ]),
            'footer_pages'  => Page::query()->where('is_published', true)->where('show_in_footer', true)
                ->orderBy('sort_order')->get(['title', 'slug']),
            'announcements' => Banner::query()->where('type', 'marquee')->live()->get(['id', 'title', 'link']),
        ]);
    }
}
