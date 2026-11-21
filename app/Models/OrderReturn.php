<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReturn extends Model
{
    public const REASONS = ['damaged', 'wrong_item', 'not_as_described', 'not_working', 'changed_mind', 'other'];
    public const STATUSES = ['requested', 'approved', 'received', 'refunded', 'rejected'];
    public const REFUND_METHODS = ['cash', 'bkash', 'nagad', 'bank'];

    protected $fillable = ['order_id', 'reason', 'details', 'status', 'refund_amount', 'refund_method', 'restock', 'admin_note'];

    protected $casts = [
        'order_id'      => 'integer',
        'refund_amount' => 'float',
        'restock'       => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class);
    }

    /** Value of the returned items at the price the customer paid. */
    public function itemsValue(): float
    {
        return round($this->items->sum(fn ($i) => $i->qty * ($i->orderItem->price ?? 0)), 2);
    }
}
