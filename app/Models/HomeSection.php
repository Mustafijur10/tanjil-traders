<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeSection extends Model
{
    use HasTranslatableText;

    public const TYPES = ['featured', 'new', 'deals', 'best_sellers', 'category', 'brand', 'categories', 'brands', 'promo'];

    protected $fillable = [
        'type', 'title', 'subtitle', 'category_id', 'brand_id', 'item_limit', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'brand_id'    => 'integer',
        'item_limit'  => 'integer',
        'sort_order'  => 'integer',
        'is_active'   => 'boolean',
    ];

    protected array $translatable = ['title', 'subtitle'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
