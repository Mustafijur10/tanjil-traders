<?php

namespace App\Models;

use App\Models\Concerns\StoresShopTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use StoresShopTime;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'sku', 'short_description', 'description',
        'price', 'sale_price', 'sale_starts_at', 'sale_ends_at', 'cost_price', 'stock_qty', 'warranty',
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
        'sale_starts_at' => 'datetime',
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

    /** True while a sale price is set and now is inside its start and end time (both optional). */
    public function onSale(): bool
    {
        return $this->sale_price !== null
            && $this->price !== null
            && $this->sale_price < $this->price
            && $this->saleWindowOpen();
    }

    /** The sale period has started and not ended (used for option prices too). */
    public function saleWindowOpen(): bool
    {
        return ($this->sale_starts_at === null || ! $this->sale_starts_at->isFuture())
            && ($this->sale_ends_at === null || $this->sale_ends_at->isFuture());
    }

    /**
     * The same rule in SQL. "Now" comes from the app clock (Asia/Dhaka), the same clock the
     * dates are saved with, so it is right on every database and server timezone.
     */
    public static function saleLiveSql(string $table = 'products'): string
    {
        $now = "'" . now()->format('Y-m-d H:i:s') . "'";

        return "({$table}.sale_price IS NOT NULL AND {$table}.sale_price < {$table}.price"
            . " AND ({$table}.sale_starts_at IS NULL OR {$table}.sale_starts_at <= {$now})"
            . " AND ({$table}.sale_ends_at IS NULL OR {$table}.sale_ends_at > {$now}))";
    }

    public function scopeOnSaleNow(Builder $q): Builder
    {
        return $q->whereRaw(self::saleLiveSql($q->getModel()->getTable()));
    }

    // Both accessors cope with products loaded with only a few columns (e.g. "product:id,name").
    public function getFinalPriceAttribute(): ?float
    {
        if ($this->price === null) {
            return null;
        }

        return $this->onSale() ? $this->sale_price : $this->price;
    }

    public function getInStockAttribute(): ?bool
    {
        return $this->stock_qty === null ? null : $this->stock_qty > 0;
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
