<?php

namespace App\Services;

use App\Models\FlashSale;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A flash sale writes its price onto each product (and each option) together with its start and end time.
 * The shop then treats it like any other sale: it starts and stops by itself, shows the countdown,
 * and appears on the Deals page. Removing a product from the sale (or deleting the sale) clears its sale price.
 */
class FlashSaleService
{
    public function save(array $data, ?FlashSale $sale = null): FlashSale
    {
        $productIds = array_values(array_unique($data['product_ids']));
        $this->checkOverlap($productIds, $data['starts_at'], $data['ends_at'], $sale?->id);

        return DB::transaction(function () use ($data, $productIds, $sale) {
            $previous = $sale ? $sale->products()->pluck('products.id')->all() : [];
            $sale ??= new FlashSale;
            $sale->fill(collect($data)->except('product_ids')->all())->save();
            $sale->products()->sync($productIds);

            $this->clear(array_diff($previous, $productIds));
            $sale->is_active ? $this->apply($sale) : $this->clear($productIds);

            return $sale->fresh();
        });
    }

    public function delete(FlashSale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $this->clear($sale->products()->pluck('products.id')->all());
            $sale->delete();
        });
    }

    /** Write the sale prices and dates onto the products. */
    public function apply(FlashSale $sale): void
    {
        foreach ($sale->products()->with('variants')->get() as $product) {
            if ($product->variants->isNotEmpty()) {
                foreach ($product->variants as $v) {
                    $v->update(['sale_price' => $sale->priceFor($v->price)]);
                }
                $product->syncFromVariants();
                $product->refresh();
            } else {
                $product->sale_price = $sale->priceFor($product->price);
            }
            $product->forceFill([
                'sale_price'     => $product->sale_price,
                'sale_starts_at' => $sale->starts_at,
                'sale_ends_at'   => $sale->ends_at,
            ])->saveQuietly();
        }
    }

    /** Remove sale prices and dates from these products. */
    public function clear(array $productIds): void
    {
        if (! $productIds) {
            return;
        }
        foreach (Product::with('variants')->whereIn('id', $productIds)->get() as $product) {
            $product->variants()->update(['sale_price' => null]);
            $product->forceFill(['sale_price' => null, 'sale_starts_at' => null, 'sale_ends_at' => null])->saveQuietly();
            $product->syncFromVariants();
        }
    }

    /** A product can only be in one flash sale at a time. */
    private function checkOverlap(array $productIds, string $starts, string $ends, ?int $ignoreId): void
    {
        $clash = DB::table('flash_sale_items')
            ->join('flash_sales', 'flash_sales.id', '=', 'flash_sale_items.flash_sale_id')
            ->join('products', 'products.id', '=', 'flash_sale_items.product_id')
            ->whereIn('flash_sale_items.product_id', $productIds)
            ->where('flash_sales.is_active', true)
            ->when($ignoreId, fn ($q) => $q->where('flash_sales.id', '!=', $ignoreId))
            ->where('flash_sales.starts_at', '<', $ends)
            ->where('flash_sales.ends_at', '>', $starts)
            ->first(['products.name', 'flash_sales.name as sale']);

        if ($clash) {
            throw ValidationException::withMessages(['product_ids' => "{$clash->name} is already in the flash sale “{$clash->sale}” at that time."]);
        }
    }
}
