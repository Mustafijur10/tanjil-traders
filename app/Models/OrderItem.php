<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'product_id', 'variant_id', 'name', 'variant_label', 'sku',
        'price', 'cost_price', 'qty', 'total',
    ];

    protected $casts = [
        'order_id'   => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'price'      => 'float',
        'cost_price' => 'float',
        'qty'        => 'integer',
        'total'      => 'float',
    ];

    protected $hidden = ['cost_price'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
