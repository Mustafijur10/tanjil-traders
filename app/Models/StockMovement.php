<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class StockMovement extends Model
{
    public const UPDATED_AT = null;
    public const REASONS = ['sale', 'restock', 'adjustment', 'return', 'cancel'];

    protected $fillable = ['product_id', 'variant_id', 'change', 'balance', 'reason', 'order_id', 'note', 'user_id'];

    protected $casts = [
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'change'     => 'integer',
        'balance'    => 'integer',
        'order_id'   => 'integer',
        'user_id'    => 'integer',
    ];

    /**
     * The only way stock should change: updates the product (or variant) stock and logs the movement.
     */
    public static function record(Product $product, ?ProductVariant $variant, int $change, string $reason, array $extra = []): self
    {
        return DB::transaction(function () use ($product, $variant, $change, $reason, $extra) {
            $target = $variant ?? $product;
            $target->newQuery()->whereKey($target->id)->lockForUpdate()->first();
            $target->increment('stock_qty', $change);
            $target->refresh();

            if ($variant) {
                $product->syncFromVariants();
            }

            return static::create([
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'change'     => $change,
                'balance'    => $target->stock_qty,
                'reason'     => $reason,
                'order_id'   => $extra['order_id'] ?? null,
                'note'       => $extra['note'] ?? null,
                'user_id'    => $extra['user_id'] ?? auth()->id(),
            ]);
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
