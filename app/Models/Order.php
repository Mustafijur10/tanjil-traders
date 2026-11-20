<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
    public const PAYMENT_METHODS = ['cod', 'bkash', 'nagad'];
    public const PAYMENT_STATUSES = ['unpaid', 'paid', 'refunded'];

    protected $fillable = [
        'order_number', 'user_id', 'name', 'phone', 'email', 'address', 'city', 'note',
        'shipping_method_id', 'subtotal', 'shipping_cost', 'coupon_code', 'discount', 'total',
        'payment_method', 'payment_status', 'transaction_id', 'status', 'source',
    ];

    protected $casts = [
        'user_id'            => 'integer',
        'shipping_method_id' => 'integer',
        'subtotal'           => 'float',
        'shipping_cost'      => 'float',
        'discount'           => 'float',
        'total'              => 'float',
    ];

    protected static function booted(): void
    {
        // TT-2611-0001 style numbers: prefix, year+month, running number.
        static::created(function (Order $order) {
            if (! $order->order_number) {
                $order->forceFill([
                    'order_number' => 'TT-' . now()->format('ym') . '-' . str_pad((string) $order->id, 4, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('created_at');
    }
}
