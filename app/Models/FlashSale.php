<?php

namespace App\Models;

use App\Models\Concerns\StoresShopTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FlashSale extends Model
{
    use StoresShopTime;

    protected $fillable = ['name', 'discount_type', 'discount_value', 'starts_at', 'ends_at', 'is_active'];

    protected $casts = [
        'discount_value' => 'float',
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
        'is_active'      => 'boolean',
    ];

    protected $appends = ['state'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'flash_sale_items');
    }

    /** paused | scheduled | live | ended */
    public function getStateAttribute(): string
    {
        return match (true) {
            ! $this->is_active            => 'paused',
            $this->ends_at?->isPast()     => 'ended',
            $this->starts_at?->isFuture() => 'scheduled',
            default                       => 'live',
        };
    }

    /** Sale price for a regular price, rounded to whole taka. */
    public function priceFor(float $price): float
    {
        $sale = $this->discount_type === 'percent'
            ? $price * (1 - $this->discount_value / 100)
            : $price - $this->discount_value;

        return max(0, round($sale));
    }
}
