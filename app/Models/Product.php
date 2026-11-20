<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'sku', 'short_description', 'description',
        'price', 'sale_price', 'sale_ends_at', 'cost_price', 'stock_qty', 'warranty',
        'is_featured', 'status', 'meta_title', 'meta_description',
    ];

    protected $casts = [
        'category_id'  => 'integer',
        'brand_id'     => 'integer',
        'price'        => 'float',
        'sale_price'   => 'float',
        'cost_price'   => 'float',
        'stock_qty'    => 'integer',
        'is_featured'  => 'boolean',
        'rating_avg'   => 'float',
        'review_count' => 'integer',
        'sale_ends_at' => 'datetime',
    ];

    protected $hidden = ['cost_price'];

    protected $appends = ['final_price', 'in_stock'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    /** True while a sale price is set and its end date (if any) has not passed. */
    public function onSale(): bool
    {
        return $this->sale_price !== null
            && $this->sale_price < $this->price
            && ($this->sale_ends_at === null || $this->sale_ends_at->isFuture());
    }

    public function getFinalPriceAttribute(): float
    {
        return $this->onSale() ? $this->sale_price : $this->price;
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock_qty > 0;
    }

    /** After variants change: product price = cheapest active variant, stock = total stock. */
    public function syncFromVariants(): void
    {
        $variants = $this->variants()->where('is_active', true)->get();
        if ($variants->isEmpty()) {
            return;
        }

        $cheapest = $variants->sortBy(fn ($v) => $v->sale_price ?? $v->price)->first();
        $this->forceFill([
            'price'      => $cheapest->price,
            'sale_price' => $cheapest->sale_price,
            'stock_qty'  => $variants->sum('stock_qty'),
        ])->saveQuietly();
    }

    /** Recalculate the cached rating from approved reviews. */
    public function refreshRating(): void
    {
        $approved = $this->reviews()->where('is_approved', true);
        $this->forceFill([
            'rating_avg'   => round((float) $approved->avg('rating'), 1),
            'review_count' => $approved->count(),
        ])->saveQuietly();
    }
}
