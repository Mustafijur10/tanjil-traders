<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\Slug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /** Main categories with their subcategories, product counts and spec ids. */
    public function index()
    {
        $counts = DB::table('products')->selectRaw('category_id, count(*) as total')->groupBy('category_id')->pluck('total', 'category_id');

        $all = Category::query()
            ->with('attributes:id')
            ->orderBy('sort_order')->orderBy('name')
            ->get()
            ->map(function (Category $c) use ($counts) {
                $c->setAttribute('product_count', (int) ($counts[$c->id] ?? 0));
                $c->setAttribute('attribute_ids', $c->attributes->pluck('id')->values());
                $c->unsetRelation('attributes');

                return $c;
            });

        $children = $all->whereNotNull('parent_id')->groupBy('parent_id');

        return $all->whereNull('parent_id')->values()->map(function (Category $c) use ($children) {
            $kids = ($children[$c->id] ?? collect())->values();
            $c->setAttribute('children', $kids);
            $c->setAttribute('total_products', $c->product_count + $kids->sum('product_count'));

            return $c;
        });
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Slug::unique(Category::class, $data['slug'] ?? null, $data['name']);
        $data['sort_order'] ??= (int) Category::where('parent_id', $data['parent_id'] ?? null)->max('sort_order') + 1;

        $category = Category::create($data);
        $this->syncAttributes($category, $request);

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request, $category);
        $data['slug'] = Slug::unique(Category::class, $data['slug'] ?? null, $data['name'], $category->id);

        $category->update($data);
        $this->syncAttributes($category, $request);

        return $category;
    }

    public function destroy(Category $category)
    {
        $ids = $category->selfAndChildIds();
        $products = DB::table('products')->whereIn('category_id', $ids)->count();
        if ($products) {
            return response()->json([
                'message' => "This category still has {$products} products. Move them to another category first.",
            ], 422);
        }

        $category->delete();

        return response()->noContent();
    }

    /** Body: { items: [{ id, parent_id, sort_order }] } after drag and drop. */
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer', 'exists:categories,id'],
            'items.*.parent_id'  => ['nullable', 'integer', 'exists:categories,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $item) {
                if ($item['parent_id'] === $item['id']) {
                    continue;
                }
                Category::whereKey($item['id'])->update(['parent_id' => $item['parent_id'], 'sort_order' => $item['sort_order']]);
            }
        });

        return response()->json(['message' => 'Order saved.']);
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'parent_id'        => [
                'nullable', 'integer',
                // Only two levels: a parent must be a main category and cannot be the category itself.
                Rule::exists('categories', 'id')->whereNull('parent_id'),
                Rule::notIn([$category?->id]),
            ],
            'name'             => ['required', 'string', 'max:120'],
            'slug'             => ['nullable', 'string', 'max:140', 'alpha_dash'],
            'icon'             => ['nullable', 'string', 'max:60'],
            'image'            => ['nullable', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:5000'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'show_in_nav'      => ['boolean'],
            'is_featured'      => ['boolean'],
            'is_active'        => ['boolean'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
        ]) + ['parent_id' => null];
    }

    /** Which specs this category uses. Body: attribute_ids: [3, 1, 7] in display order. */
    private function syncAttributes(Category $category, Request $request): void
    {
        if (! $request->has('attribute_ids')) {
            return;
        }
        $ids = $request->validate(['attribute_ids' => ['array'], 'attribute_ids.*' => ['integer', 'exists:attributes,id']])['attribute_ids'] ?? [];
        $category->attributes()->sync(collect($ids)->values()->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i]]));
    }
}
