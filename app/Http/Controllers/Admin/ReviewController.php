<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,approved'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $items = Review::query()
            ->with(['product:id,name,slug', 'product.images' => fn ($q) => $q->orderBy('sort_order')->limit(1)])
            ->when($request->status === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($request->status === 'approved', fn ($q) => $q->where('is_approved', true))
            ->when($request->rating, fn ($q, $r) => $q->where('rating', $r))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('comment', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%")
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$s}%"))))
            ->latest()
            ->paginate(20);

        return response()->json([
            'items'  => $items,
            'counts' => [
                'all'      => Review::count(),
                'pending'  => Review::where('is_approved', false)->count(),
                'approved' => Review::where('is_approved', true)->count(),
            ],
        ]);
    }

    /** Approve / hide a review, or write the store's public reply. */
    public function update(Request $request, Review $review)
    {
        $data = $request->validate([
            'is_approved' => ['sometimes', 'boolean'],
            'reply'       => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);
        $review->update($data);

        return $review->load('product:id,name,slug');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return response()->noContent();
    }
}
