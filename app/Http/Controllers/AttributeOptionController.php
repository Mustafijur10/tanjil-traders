<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Attribute;
use App\Models\AttributeOption;

class AttributeOptionController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // GET /api/attribute-options?parent_attribute_option_id=X
    // Used by all drill-down views (View Sub-Categories, View Models etc.)
    // ─────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        try {
            $query = AttributeOption::query();

            if ($request->filled('parent_attribute_option_id')) {
                $query->where('parent_attribute_option_id', $request->parent_attribute_option_id);
            }

            if ($request->filled('attribute_id')) {
                $query->where('attribute_id', $request->attribute_id);
            }

            if ($request->filled('slug')) {
                $query->whereHas('attribute', fn($q) => $q->where('slug', $request->slug));
            }

            $options = $query->orderBy('title')->get();

            return response()->json([
                'success' => true,
                'data'    => $options,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /api/save-option
    // slug param tells us which attribute row to use (maker/model/category etc.)
    // parent_attribute_option_id = 0 for top-level, or parent row id for children
    // ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug'  => 'required|string',
        ]);

        try {
            $attribute = Attribute::where('slug', $request->slug)->firstOrFail();

            $option = AttributeOption::create([
                'title'                      => trim($request->title),
                'attribute_id'               => $attribute->id,
                'parent_attribute_option_id' => $request->parent_attribute_option_id ?? 0,
            ]);

            // Return updated sibling list (same parent)
            $siblings = AttributeOption::where('attribute_id', $attribute->id)
                ->where('parent_attribute_option_id', $option->parent_attribute_option_id)
                ->orderBy('title')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Saved successfully.',
                'data'    => $siblings,
                'option'  => $option,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // PUT /api/attribute-option-update
    // Only updates title — attribute_id and parent never change on edit
    // ─────────────────────────────────────────────────────────────────
    public function update(Request $request)
    {
        $request->validate([
            'id'    => 'required|integer|exists:attribute_options,id',
            'title' => 'required|string|max:255',
        ]);

        try {
            $option = AttributeOption::findOrFail($request->id);
            $option->update(['title' => trim($request->title)]);

            $siblings = AttributeOption::where('attribute_id', $option->attribute_id)
                ->where('parent_attribute_option_id', $option->parent_attribute_option_id)
                ->orderBy('title')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Updated successfully.',
                'options' => $siblings,
                'option'  => $option,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /api/remove-option
    // Recursively deletes option and ALL children (models, years, etc.)
    // ─────────────────────────────────────────────────────────────────
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:attribute_options,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $this->deleteRecursive($request->id);
            });

            return response()->json([
                'success' => true,
                'message' => 'Deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/attribute/makers
    // Top-level makers (parent = 0)
    // ─────────────────────────────────────────────────────────────────
    public function makers()
    {
        try {
            $attribute = Attribute::where('slug', 'maker')->firstOrFail();

            $makers = AttributeOption::where('attribute_id', $attribute->id)
                ->where(function ($q) {
                    $q->whereNull('parent_attribute_option_id')
                      ->orWhere('parent_attribute_option_id', 0);
                })
                ->orderBy('title')
                ->get();

            return response()->json(['success' => true, 'data' => $makers]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/attribute/category
    // Top-level categories (parent = 0), used by Category.vue allItem()
    // ─────────────────────────────────────────────────────────────────
    public function categories()
    {
        try {
            $attribute = Attribute::where('slug', 'category')->firstOrFail();

            $categories = AttributeOption::where('attribute_id', $attribute->id)
                ->where(function ($q) {
                    $q->whereNull('parent_attribute_option_id')
                      ->orWhere('parent_attribute_option_id', 0);
                })
                ->orderBy('title')
                ->get();

            return response()->json(['success' => true, 'data' => $categories]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/nav-categories
    // Top-level categories + subcategories for frontend Header nav
    // ─────────────────────────────────────────────────────────────────
    public function navCategories()
    {
        try {
            $attribute = Attribute::where('slug', 'category')->firstOrFail();

            $categories = AttributeOption::where('attribute_id', $attribute->id)
                ->where(function ($q) {
                    $q->whereNull('parent_attribute_option_id')
                      ->orWhere('parent_attribute_option_id', 0);
                })
                ->orderBy('title')
                ->get();

            $categories->each(function ($cat) use ($attribute) {
                $cat->subcategories = AttributeOption::where('attribute_id', $attribute->id)
                    ->where('parent_attribute_option_id', $cat->id)
                    ->orderBy('title')
                    ->get();
            });

            return response()->json(['success' => true, 'data' => $categories]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE: Recursively delete option and all its children
    // ─────────────────────────────────────────────────────────────────
    private function deleteRecursive(int $id): void
    {
        $children = AttributeOption::where('parent_attribute_option_id', $id)->get();
        foreach ($children as $child) {
            $this->deleteRecursive($child->id);
        }
        AttributeOption::destroy($id);
    }
}