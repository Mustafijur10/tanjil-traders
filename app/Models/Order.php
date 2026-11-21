<?php

namespace App\Models;

use App\Models\Concerns\StoresShopTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use StoresShopTime;

    public const STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
    public const PAYMENT_METHODS = ['cod', 'bkash', 'nagad'];
    public const PAYMENT_STATUSES = ['unpaid', 'paid', 'refunded'];

    /** Orders still being worked on. */
    public const OPEN = ['pending', 'confirmed', 'processing', 'shipped'];

    /** Which status can follow which. Cancelled and returned are final. */
    public const NEXT = [
        'pending'    => ['confirmed', 'cancelled'],
        'confirmed'  => ['processing', 'shipped', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['delivered', 'cancelled'],
        'delivered'  => ['returned'],
        'cancelled'  => [],
        'returned'   => [],
    ];

    protected $fillable = [
        'order_number', 'user_id', 'name', 'phone', 'email', 'address', 'city', 'note',
        'shipping_method_id', 'courier_id', 'tracking_code', 'subtotal', 'shipping_cost', 'coupon_code', 'discount', 'total',
        'payment_method', 'payment_status', 'transaction_id', 'status', 'source', 'shipped_at', 'delivered_at',
    ];

    protected $casts = [
        'user_id'            => 'integer',
        'shipping_method_id' => 'integer',
        'courier_id'         => 'integer',
        'subtotal'           => 'float',
        'shipping_cost'      => 'float',
        'discount'           => 'float',
        'total'              => 'float',
        'shipped_at'         => 'datetime',
        'delivered_at'       => 'datetime',
    ];

    protected static function booted(): void
    {
        // TT-2611-0001 style numbers: prefix, year+month, running number.
        // The id is only known after saving, so a temporary unique value is stored first.
        static::creating(function (Order $order) {
            $order->order_number ??= 'TMP-' . bin2hex(random_bytes(6));
        });
        static::created(function (Order $order) {
            if (str_starts_with($order->order_number, 'TMP-')) {
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

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('created_at')->latest('id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class)->latest();
    }

    public function trackingLink(): ?string
    {
        return $this->courier?->trackingLink($this->tracking_code);
    }

    public function canMoveTo(string $status): bool
    {
        return in_array($status, self::NEXT[$this->status] ?? [], true);
    }

    /** Customers may cancel on their own until the shop confirms the order. */
    public function customerCanCancel(): bool
    {
        return $this->status === 'pending';
    }

    /** Returns can be asked for within the return window after delivery, one open request at a time. */
    public function customerCanReturn(): bool
    {
        $days = (int) Setting::get('checkout.return_days', 7);

        return $this->status === 'delivered'
            && $days > 0
            && $this->delivered_at?->gt(now()->subDays($days))
            && ! $this->returns()->whereIn('status', ['requested', 'approved', 'received'])->exists();
    }
}
