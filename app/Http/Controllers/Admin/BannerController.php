<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Hero carousel slides, promo banners and announcement-bar (marquee) messages.
 * They share one table; `type` tells them apart.
 */
class BannerController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->validate(['type' => ['required', Rule::in(Banner::TYPES)]])['type'];

        return Banner::where('type', $type)->orderBy('sort_order')->get();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] = (int) Banner::where('type', $data['type'])->max('sort_order') + 1;

        return response()->json(Banner::create($data), 201);
    }

    public function update(Request $request, Banner $banner)
    {
        $banner->update($this->validated($request, $banner));

        return $banner;
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();

        return response()->noContent();
    }

    /** Body: { ids: [...] } in the new order. */
    public function reorder(Request $request)
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'exists:banners,id']])['ids'];
        DB::transaction(fn () => collect($ids)->each(fn ($id, $i) => Banner::whereKey($id)->update(['sort_order' => $i + 1])));

        return response()->json(['message' => 'Order saved.']);
    }

    private function validated(Request $request, ?Banner $banner = null): array
    {
        $type = $request->input('type', $banner?->type);

        return $request->validate([
            'type'         => ['required', Rule::in(Banner::TYPES)],
            // Slides and promo banners need an image; announcements need text.
            'image'        => [Rule::requiredIf($type !== 'marquee'), 'nullable', 'string', 'max:255'],
            'mobile_image' => ['nullable', 'string', 'max:255'],
            'title'        => [Rule::requiredIf($type === 'marquee'), 'nullable', 'string', 'max:255'],
            'subtitle'     => ['nullable', 'string', 'max:255'],
            'button_text'  => ['nullable', 'string', 'max:60'],
            'link'         => ['nullable', 'string', 'max:255', 'regex:/^(\/|https?:\/\/|tel:|mailto:)/'],
            'starts_at'    => ['nullable', 'date'],
            'ends_at'      => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active'    => ['boolean'],
        ], [
            'image.required'    => 'Upload an image for this banner.',
            'title.required'    => 'Write the announcement text.',
            'link.regex'        => 'The link must start with / (a page on your site) or https://.',
            'ends_at.after_or_equal' => 'The end date must be after the start date.',
        ]);
    }
}
