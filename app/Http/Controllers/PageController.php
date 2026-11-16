<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller implements HasMiddleware
{
    /** Roles allowed to manage pages. Adjust to the role values used in your users table. */
    private const ADMIN_ROLES = ['admin', 'super_admin', 'administrator'];

    /** Methods that are public (no admin role needed). Everything else is admin-only. */
    private const PUBLIC_ACTIONS = ['publicShow', 'menu', 'web', 'sitemap'];

    public static function middleware(): array
    {
        return [
            (new Middleware(function ($request, $next) {
                $role = strtolower((string) optional($request->user())->role);
                abort_unless(in_array($role, self::ADMIN_ROLES, true), 403, 'You are not allowed to manage pages.');

                return $next($request);
            }))->except(self::PUBLIC_ACTIONS),
        ];
    }

    // ═════════════════════════ Admin API (auth:sanctum + admin role) ═════════════════════════

    public function index(Request $r)
    {
        $q = Page::query()->select([
            'id', 'title', 'slug', 'status', 'visibility', 'meta_title', 'meta_description',
            'display_order', 'published_at', 'created_at', 'updated_at',
        ]);

        if (in_array($r->query('status'), ['draft', 'published'], true)) {
            $q->where('status', $r->query('status'));
        }
        if ($term = trim((string) $r->query('q', ''))) {
            $q->where(fn ($w) => $w->where('title', 'like', "%{$term}%")->orWhere('slug', 'like', "%{$term}%"));
        }

        $sortable = ['title', 'slug', 'status', 'created_at', 'updated_at', 'display_order'];
        $by = in_array($r->query('sort_by'), $sortable, true) ? $r->query('sort_by') : 'updated_at';
        $dir = $r->query('sort_type') === 'asc' ? 'asc' : 'desc';

        $p = $q->orderBy($by, $dir)->paginate(max(1, min((int) $r->query('per_page', 10), 100)));

        return response()->json([
            'success' => true,
            'data' => $p->items(),
            'meta' => ['total' => $p->total(), 'current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'per_page' => $p->perPage()],
            'counts' => [
                'all' => Page::count(),
                'published' => Page::published()->count(),
                'draft' => Page::where('status', 'draft')->count(),
            ],
        ]);
    }

    public function show($id)
    {
        return response()->json(['success' => true, 'data' => Page::findOrFail($id)]);
    }

    public function store(Request $r)
    {
        $data = $this->validated($r);
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        return response()->json(['success' => true, 'data' => Page::create($data)->fresh()], 201);
    }

    public function update(Request $r, $id)
    {
        $page = Page::findOrFail($id);
        $data = $this->validated($r, $page->id);

        if ($data['status'] !== $page->status) {
            $data['published_at'] = $data['status'] === 'published' ? now() : null;
        }
        $page->update($data);

        return response()->json(['success' => true, 'data' => $page->fresh()]);
    }

    public function setStatus(Request $r, $id)
    {
        $page = Page::findOrFail($id);
        $status = $r->validate(['status' => ['required', Rule::in(['draft', 'published'])]])['status'];

        if ($status === 'published' && ! filled(strip_tags((string) $page->content))) {
            return response()->json(['success' => false, 'message' => 'Add some content before publishing this page.'], 422);
        }
        if ($status !== $page->status) {
            $page->update(['status' => $status, 'published_at' => $status === 'published' ? now() : null]);
        }

        return response()->json(['success' => true, 'data' => $page->fresh()]);
    }

    public function duplicate($id)
    {
        $src = Page::findOrFail($id);
        $copy = $src->replicate(['published_at']);
        $copy->title = 'Copy of ' . $src->title;
        $copy->status = 'draft';
        $copy->published_at = null;

        $base = $slug = Str::limit($src->slug . '-copy', 180, '');
        for ($i = 2; Page::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }
        $copy->slug = $slug;
        $copy->save();

        return response()->json(['success' => true, 'data' => $copy], 201);
    }

    public function destroy($id)
    {
        Page::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    /** Editor / featured / OG image upload. Real images only, max 4 MB, random file name. */
    public function upload(Request $r)
    {
        $r->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:4096|dimensions:max_width=6000,max_height=6000']);
        $path = $r->file('image')->store('cms/' . date('Y/m'), 'public');

        return response()->json(['success' => true, 'url' => '/storage/' . $path]);
    }

    // ═════════════════════════ Public API (published pages only) ═════════════════════════

    public function publicShow(string $slug)
    {
        $p = Page::published()->where('slug', $slug)->first();
        if (! $p) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        // Only what the storefront needs. No ids, status or display flags.
        return response()->json(['success' => true, 'data' => [
            'title' => $p->title,
            'slug' => $p->slug,
            'short_description' => $p->short_description,
            'content' => $p->content,
            'featured_image' => $p->featured_image,
            'meta_title' => $p->meta_title,
            'meta_description' => $p->meta_description,
            'meta_keywords' => $p->meta_keywords,
            'canonical_url' => $p->canonical_url,
            'og_title' => $p->og_title,
            'og_description' => $p->og_description,
            'og_image' => $p->og_image,
            'robots' => $p->robotsContent(),
            'updated_at' => $p->updated_at,
        ]]);
    }

    /** Links for the storefront footer and main navigation. */
    public function menu()
    {
        $rows = Page::published()->where('visibility', 'public')
            ->where(fn ($w) => $w->where('show_in_footer', true)->orWhere('show_in_navigation', true))
            ->orderBy('display_order')->orderBy('title')
            ->get(['title', 'slug', 'show_in_footer', 'show_in_navigation']);

        $pick = fn ($flag) => $rows->where($flag, true)->map->only(['title', 'slug'])->values();

        return response()->json(['success' => true, 'data' => [
            'footer' => $pick('show_in_footer'),
            'navigation' => $pick('show_in_navigation'),
        ]]);
    }

    // ═════════════════════════ Web (server-rendered SEO + sitemap) ═════════════════════════

    /** Serves the SPA shell, with the page passed in so <head> meta tags are rendered by the server. */
    public function web(string $slug)
    {
        $page = in_array($slug, Page::RESERVED, true) ? null : Page::published()->where('slug', $slug)->first();

        return view('app', ['page' => $page]);
    }

    public function sitemap()
    {
        $urls = Page::published()->where('visibility', 'public')->where('show_in_sitemap', true)->where('robots_index', true)
            ->orderBy('display_order')->get(['slug', 'updated_at'])
            ->map(fn ($p) => '<url><loc>' . e(url('/' . $p->slug)) . '</loc><lastmod>' . $p->updated_at->toAtomString() . '</lastmod></url>')
            ->implode('');

        return response('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . $urls . '</urlset>', 200, ['Content-Type' => 'application/xml']);
    }

    // ═════════════════════════ Validation ═════════════════════════

    private function validated(Request $r, ?int $id = null): array
    {
        $r->merge(['slug' => Str::slug((string) ($r->input('slug') ?: $r->input('title')))]);

        $data = $r->validate([
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('pages', 'slug')->ignore($id), Rule::notIn(Page::RESERVED)],
            'short_description' => 'nullable|string|max:500',
            'content' => [Rule::requiredIf($r->input('status') === 'published'), 'nullable', 'string', 'max:1000000'],
            'featured_image' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500',
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
            'show_in_footer' => 'boolean',
            'show_in_navigation' => 'boolean',
            'show_in_sitemap' => 'boolean',
            'display_order' => 'nullable|integer|min:0|max:100000',
            'visibility' => ['required', Rule::in(['public', 'unlisted'])],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ], [
            'slug.required' => 'Enter a URL slug (Latin letters and numbers).',
            'slug.unique' => 'This URL is already used by another page.',
            'slug.not_in' => 'This URL is reserved by the website. Choose another.',
            'slug.regex' => 'Use lowercase letters, numbers and single hyphens only.',
            'content.required_if' => 'Add some content before publishing.',
        ]);

        $data['content'] = Page::clean($data['content'] ?? '');
        $data['display_order'] = (int) ($data['display_order'] ?? 0);

        if ($r->input('status') === 'published' && ! filled(strip_tags($data['content']) ?: (str_contains($data['content'], '<img') ? 'img' : ''))) {
            abort(response()->json(['success' => false, 'message' => 'The content is empty after cleaning. Add some text or an image.', 'errors' => ['content' => ['Add some content before publishing.']]], 422));
        }

        return $data;
    }
}
