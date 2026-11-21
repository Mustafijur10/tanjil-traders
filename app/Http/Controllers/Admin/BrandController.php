<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Support\Slug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    public function index()
    {
        return Brand::query()->withCount('products')->orderBy('sort_order')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Slug::unique(Brand::class, $data['slug'] ?? null, $data['name']);
        $data['sort_order'] ??= (int) Brand::max('sort_order') + 1;

        return response()->json(Brand::create($data)->loadCount('products'), 201);
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $this->validated($request);
        $data['slug'] = Slug::unique(Brand::class, $data['slug'] ?? null, $data['name'], $brand->id);
        $brand->update($data);

        return $brand->loadCount('products');
    }

    /** Products of a deleted brand stay in the shop without a brand. */
    public function destroy(Brand $brand)
    {
        $brand->delete();

        return response()->noContent();
    }

    /** Body: { ids: [5, 2, 9] } in the new order. */
    public function reorder(Request $request)
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'exists:brands,id']])['ids'];
        DB::transaction(fn () => collect($ids)->each(fn ($id, $i) => Brand::whereKey($id)->update(['sort_order' => $i + 1])));

        return response()->json(['message' => 'Order saved.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'             => ['required', 'string', 'max:120'],
            'slug'             => ['nullable', 'string', 'max:140', 'alpha_dash'],
            'logo'             => ['nullable', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:5000'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'is_featured'      => ['boolean'],
            'is_active'        => ['boolean'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
