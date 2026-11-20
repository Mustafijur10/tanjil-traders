<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['product_id', 'user_id', 'name', 'rating', 'comment', 'reply', 'is_approved'];

    protected $casts = [
        'product_id'  => 'integer',
        'user_id'     => 'integer',
        'rating'      => 'integer',
        'is_approved' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Keep the product's cached rating correct.
        static::saved(fn (Review $r) => $r->product?->refreshRating());
        static::deleted(fn (Review $r) => $r->product?->refreshRating());
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
