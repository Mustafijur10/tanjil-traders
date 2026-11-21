<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\Html;
use App\Support\Slug;
use Illuminate\Http\Request;

/** Information pages: About us, Warranty policy, Return policy, Privacy, Terms ... */
class PageController extends Controller
{
    /** Pages that cannot use these addresses because the shop already uses them. */
    private const RESERVED = ['admin', 'api', 'storage', 'product', 'products', 'category', 'brand', 'brands', 'search', 'cart', 'checkout',
        'compare', 'account', 'login', 'register', 'wishlist', 'track-order', 'contact', 'deals', 'shop', 'reset-password', 'page'];

    public function index()
    {
        return Page::query()->orderBy('sort_order')->orderBy('title')->get(['id', 'title', 'slug', 'show_in_footer', 'is_published', 'sort_order', 'updated_at', 'meta_title', 'meta_description']);
    }

    public function show(Page $page)
    {
        return $page;
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Slug::unique(Page::class, $data['slug'] ?? null, $data['title']);
        $data['sort_order'] = (int) Page::max('sort_order') + 1;

        return response()->json(Page::create($data), 201);
    }

    public function update(Request $request, Page $page)
    {
        $data = $this->validated($request);
        $data['slug'] = Slug::unique(Page::class, $data['slug'] ?? null, $data['title'], $page->id);
        $page->update($data);

        return $page;
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255', 'alpha_dash', 'not_in:' . implode(',', self::RESERVED)],
            'content'          => ['nullable', 'string', 'max:200000'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'show_in_footer'   => ['boolean'],
            'is_published'     => ['boolean'],
        ], [
            'slug.not_in' => 'This web address is used by the shop. Please choose another.',
        ]);
        $data['content'] = Html::clean($data['content'] ?? null);

        return $data;
    }
}
