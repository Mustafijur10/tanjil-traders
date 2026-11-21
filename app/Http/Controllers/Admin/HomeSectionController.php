<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HomeSectionController extends Controller
{
    public function index()
    {
        return HomeSection::with(['category:id,name', 'brand:id,name'])->orderBy('sort_order')->get();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] = (int) HomeSection::max('sort_order') + 1;

        return response()->json(HomeSection::create($data)->load(['category:id,name', 'brand:id,name']), 201);
    }

    public function update(Request $request, HomeSection $homeSection)
    {
        $homeSection->update($this->validated($request));

        return $homeSection->load(['category:id,name', 'brand:id,name']);
    }

    public function destroy(HomeSection $homeSection)
    {
        $homeSection->delete();

        return response()->noContent();
    }

    public function reorder(Request $request)
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'exists:home_sections,id']])['ids'];
        DB::transaction(fn () => collect($ids)->each(fn ($id, $i) => HomeSection::whereKey($id)->update(['sort_order' => $i + 1])));

        return response()->json(['message' => 'Order saved.']);
    }

    private function validated(Request $request): array
    {
        $type = $request->input('type');

        $data = $request->validate([
            'type'        => ['required', Rule::in(HomeSection::TYPES)],
            'title'       => ['required', 'string', 'max:120'],
            'subtitle'    => ['nullable', 'string', 'max:255'],
            'category_id' => [Rule::requiredIf($type === 'category'), 'nullable', 'integer', 'exists:categories,id'],
            'brand_id'    => [Rule::requiredIf($type === 'brand'), 'nullable', 'integer', 'exists:brands,id'],
            'item_limit'  => ['required', 'integer', 'between:2,24'],
            'is_active'   => ['boolean'],
        ], [
            'category_id.required' => 'Choose which category to show.',
            'brand_id.required'    => 'Choose which brand to show.',
        ]);

        // Only keep the link that matters for this section type.
        $data['category_id'] = $type === 'category' ? $data['category_id'] : null;
        $data['brand_id'] = $type === 'brand' ? $data['brand_id'] : null;

        return $data;
    }
}
