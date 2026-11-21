<?php

namespace App\Models;

use App\Models\Concerns\StoresShopTime;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use StoresShopTime;

    protected $fillable = [
        'code', 'type', 'value', 'min_order', 'max_discount',
        'usage_limit', 'used_count', 'starts_at', 'ends_at', 'is_active',
    ];

    protected $casts = [
        'value'        => 'float',
        'min_order'    => 'float',
        'max_discount' => 'float',
        'usage_limit'  => 'integer',
        'used_count'   => 'integer',
        'starts_at'    => 'datetime',
        'ends_at'      => 'datetime',
        'is_active'    => 'boolean',
    ];

    /** Returns an error message, or null when the coupon can be used for this subtotal. */
    public function problemFor(float $subtotal): ?string
    {
        return match (true) {
            ! $this->is_active                                         => 'This coupon is not active.',
            $this->starts_at && $this->starts_at->isFuture()           => 'This coupon is not active yet.',
            $this->ends_at && $this->ends_at->isPast()                 => 'This coupon has expired.',
            $this->usage_limit !== null && $this->used_count >= $this->usage_limit => 'This coupon has been fully used.',
            $this->min_order !== null && $subtotal < $this->min_order  => 'Order total is too low for this coupon.',
            default                                                    => null,
        };
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'percent' ? $subtotal * $this->value / 100 : $this->value;
        if ($this->max_discount !== null) {
            $discount = min($discount, $this->max_discount);
        }

        return round(min($discount, $subtotal), 2);
    }
}
