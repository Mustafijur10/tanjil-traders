<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     * Filter by parent_id (defaults to top-level if parent_id is 0 or null).
     */
    public function index(Request $request)
    {
        try {
            $query = Category::query();

            if ($request->has('parent_id')) {
                $parentId = $request->parent_id;
                if ($parentId === '0' || $parentId === 'null' || $parentId === null || $parentId === '') {
                    $query->topLevel();
                } else {
                    $query->where('parent_id', (int) $parentId);
                }
            } else {
                $query->topLevel();
            }

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $categories = $query->with('children')
                                ->orderBy('order', 'asc')
                                ->orderBy('title', 'asc')
                                ->get();

            return response()->json([
                'success' => true,
                'data'    => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load categories: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created category, with optional inline sub-categories.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'parent_id'       => 'nullable|integer',
            'slug'            => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'icon'            => 'nullable|string|max:100',
            'accent_color'    => 'nullable|string|max:20',
            'status'          => 'nullable|string|in:Active,Draft',
            'show_in_nav'     => 'nullable|boolean',
            'featured'        => 'nullable|boolean',
            'allow_reviews'   => 'nullable|boolean',
            'order'           => 'nullable|integer',
            'sub_categories'  => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $parentId = !empty($validated['parent_id']) ? (int) $validated['parent_id'] : null;

            $slug = !empty($validated['slug'])
                ? Str::slug($validated['slug'])
                : Category::generateUniqueSlug($validated['title']);

            $category = Category::create([
                'parent_id'     => $parentId,
                'title'         => $validated['title'],
                'slug'          => $slug,
                'description'   => $validated['description'] ?? null,
                'icon'          => $validated['icon'] ?? 'mdi-tag-outline',
                'accent_color'  => $validated['accent_color'] ?? '#0f9d6b',
                'status'        => $validated['status'] ?? 'Active',
                'show_in_nav'   => $validated['show_in_nav'] ?? true,
                'featured'      => $validated['featured'] ?? false,
                'allow_reviews' => $validated['allow_reviews'] ?? true,
                'order'         => $validated['order'] ?? 0,
            ]);

            if (!empty($validated['sub_categories']) && is_array($validated['sub_categories'])) {
                foreach ($validated['sub_categories'] as $subItem) {
                    $subTitle = is_array($subItem) ? ($subItem['title'] ?? '') : (string) $subItem;
                    $subTitle = trim($subTitle);

                    if ($subTitle !== '') {
                        Category::create([
                            'parent_id'     => $category->id,
                            'title'         => $subTitle,
                            'slug'          => Category::generateUniqueSlug($subTitle),
                            'icon'          => $category->icon,
                            'accent_color'  => $category->accent_color,
                            'status'        => 'Active',
                            'show_in_nav'   => true,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully.',
                'data'    => $category->load('children'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified category with parent and children.
     */
    public function show($id)
    {
        try {
            $category = Category::with(['parent', 'children'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => $category,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'parent_id'       => 'nullable|integer',
            'slug'            => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'icon'            => 'nullable|string|max:100',
            'accent_color'    => 'nullable|string|max:20',
            'status'          => 'nullable|string|in:Active,Draft',
            'show_in_nav'     => 'nullable|boolean',
            'featured'        => 'nullable|boolean',
            'allow_reviews'   => 'nullable|boolean',
            'order'           => 'nullable|integer',
        ]);

        try {
            $parentId = !empty($validated['parent_id']) ? (int) $validated['parent_id'] : null;

            if ($parentId === $category->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'A category cannot be its own parent.',
                ], 422);
            }

            $slug = !empty($validated['slug'])
                ? Str::slug($validated['slug'])
                : ($category->title !== $validated['title']
                    ? Category::generateUniqueSlug($validated['title'], $category->id)
                    : $category->slug);

            $category->update([
                'parent_id'     => $parentId,
                'title'         => $validated['title'],
                'slug'          => $slug,
                'description'   => $validated['description'] ?? $category->description,
                'icon'          => $validated['icon'] ?? $category->icon,
                'accent_color'  => $validated['accent_color'] ?? $category->accent_color,
                'status'        => $validated['status'] ?? $category->status,
                'show_in_nav'   => $request->has('show_in_nav') ? (bool) $validated['show_in_nav'] : $category->show_in_nav,
                'featured'      => $request->has('featured') ? (bool) $validated['featured'] : $category->featured,
                'allow_reviews' => $request->has('allow_reviews') ? (bool) $validated['allow_reviews'] : $category->allow_reviews,
                'order'         => $validated['order'] ?? $category->order,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully.',
                'data'    => $category->load('children'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified category and its children.
     */
    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk store multiple sub-categories under a given parent.
     */
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'required|integer|exists:categories,id',
            'titles'    => 'required|array|min:1',
            'titles.*'  => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $parent = Category::findOrFail($validated['parent_id']);
            $created = [];

            foreach ($validated['titles'] as $title) {
                $title = trim($title);
                if ($title === '') continue;

                $sub = Category::create([
                    'parent_id'    => $parent->id,
                    'title'        => $title,
                    'slug'         => Category::generateUniqueSlug($title),
                    'icon'         => $parent->icon,
                    'accent_color' => $parent->accent_color,
                    'status'       => 'Active',
                    'show_in_nav'  => true,
                ]);
                $created[] = $sub;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($created) . ' sub-categories created successfully.',
                'data'    => $created,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Bulk create failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get categories for Storefront Header navigation.
     * Fully compatible with Header.vue expectations.
     */
    public function navCategories()
    {
        try {
            if (Category::count() === 0) {
                return (new AttributeOptionController)->navCategories();
            }

            $categories = Category::topLevel()
                ->active()
                ->inNav()
                ->with(['children' => function ($query) {
                    $query->active()->orderBy('order', 'asc')->orderBy('title', 'asc');
                }])
                ->orderBy('order', 'asc')
                ->orderBy('title', 'asc')
                ->get();

            $categories->each(function ($cat) {
                $cat->subcategories = $cat->children;
            });

            return response()->json([
                'success' => true,
                'data'    => $categories,
            ]);
        } catch (\Exception $e) {
            try {
                return (new AttributeOptionController)->navCategories();
            } catch (\Exception $ex) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        }
    }

    /**
     * Get nested category tree hierarchy.
     */
    public function tree()
    {
        try {
            $tree = Category::topLevel()
                ->with('allChildren')
                ->orderBy('order', 'asc')
                ->orderBy('title', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $tree,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}