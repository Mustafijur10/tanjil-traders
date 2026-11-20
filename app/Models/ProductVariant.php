<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'sku', 'price', 'sale_price', 'stock_qty', 'image', 'is_active'];

    protected $casts = [
        'product_id' => 'integer',
        'price'      => 'float',
        'sale_price' => 'float',
        'stock_qty'  => 'integer',
        'is_active'  => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function values(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_values', 'variant_id');
    }

    /** "8GB / 256GB / Black" */
    public function label(): string
    {
        return $this->values->pluck('value')->implode(' / ');
    }
}
